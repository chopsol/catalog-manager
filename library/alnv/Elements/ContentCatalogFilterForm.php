<?php

namespace CatalogManager;

class ContentCatalogFilterForm extends \ContentElement {

    
    protected $arrForm = [];
    protected $blnReady = false;
    protected $arrFormFields = [];
    protected $strTemplate = 'ce_catalog_filterform';


    private $arrTemplateMap = [
      
        'text' => 'ctlg_form_field_text',
        'radio' => 'ctlg_form_field_radio',
        'range' => 'ctlg_form_field_range',
        'select' => 'ctlg_form_field_select',
        'checkbox' => 'ctlg_form_field_checkbox',
    ];


    public function generate() {

        if ( TL_MODE == 'BE' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . utf8_strtoupper( $GLOBALS['TL_LANG']['CTE']['catalogFilterForm'][0] ) . ' ###';

            return $objTemplate->parse();
        }

        $this->blnReady = $this->initialize();

        if ( !$this->blnReady ) return null;

        if ( $this->arrForm['disableOnAutoItem'] && !Toolkit::isEmpty( \Input::get( 'auto_item' ) ) ) {

            return null;
        }

        if ( TL_MODE == 'FE' && !Toolkit::isEmpty( $this->customCatalogElementTpl ) ) $this->strTemplate = $this->customCatalogElementTpl;

        return parent::generate();
    }


    protected function compile() {

        $arrFields = [];

        if ( !empty( $this->arrFormFields ) && is_array( $this->arrFormFields ) ) {

            foreach ( $this->arrFormFields as $strName => $arrField ) {

                $this->arrFormFields[ $strName ] = $this->parseField( $arrField );

                if ( $this->arrFormFields[ $strName ]['type'] == 'hidden' && $this->arrFormFields[ $strName ]['name'] ) {

                    $arrFields[ $strName ] = sprintf( '<input type="hidden" name="%s" value="%s">',

                        $this->arrFormFields[ $strName ]['name'],
                        $this->arrFormFields[ $strName ]['value']
                    );

                    continue;
                }

                if ( $this->arrFormFields[ $strName ]['dependOnField'] ) {

                    $varValue = $this->getInput( $this->arrFormFields[ $strName ]['dependOnField'] );

                    if ( empty( $varValue ) && is_array( $varValue ) ) {

                        continue;

                    }elseif( count( $varValue ) <= 1 && Toolkit::isEmpty( $varValue[0] ) ) {

                       continue;
                    }

                    if ( Toolkit::isEmpty( $varValue ) ) {

                        continue;
                    }
                }

                $strTemplate = $arrField['template'] ? $arrField['template'] : $this->arrTemplateMap[ $arrField['type'] ];

                if ( !$strTemplate ) continue;

                $objTemplate = new \FrontendTemplate( $strTemplate );
                $objTemplate->setData( $this->arrFormFields[ $strName ] );

                $arrFields[ $strName ] = $objTemplate->parse();
            }
        }

        $arrAttributes = Toolkit::deserialize( $this->arrForm['attributes'] );
        $this->arrForm['formID'] = $arrAttributes[0] ? $arrAttributes[0] : 'id_form_' . $this->id;

        $this->Template->fields = $arrFields;
        $this->Template->reset = $this->getResetLink();
        $this->Template->action = $this->getActionAttr();
        $this->Template->method = $this->getMethodAttr();
        $this->Template->formSubmit = md5( 'tl_filter' );
        $this->Template->disableSubmit = $this->arrForm['disableSubmit'];
        $this->Template->cssClass = $arrAttributes[1] ? $arrAttributes[1] : '';
        $this->Template->formID = sprintf( 'id="%s"', $this->arrForm['formID'] );
        $this->Template->submit = $GLOBALS['TL_LANG']['MSC']['CATALOG_MANAGER']['filter'];

        if ( $this->arrForm['sendJsonHeader'] ) {

            $this->import( 'CatalogAjaxController' );

            $this->CatalogAjaxController->setData([

                'form' => $this->arrForm,
                'data' => $this->arrFormFields,
                'reset' => $this->Template->reset,
                'fields' => $this->Template->fields,
                'action' => $this->Template->action,
                'method' => $this->Template->method,
                'formID' => $this->Template->formID,
                'cssClass' => $this->Template->cssClass,
            ]);

            $this->CatalogAjaxController->setType( $this->arrForm['sendJsonHeader'] );
            $this->CatalogAjaxController->setModuleID( $this->arrForm['id'] );
            $this->CatalogAjaxController->sendJsonData();
        }
    }

 
    protected function getActionAttr() {

        $strPageID = $this->arrForm['jumpTo'];

        if ( !$strPageID ) {

            global $objPage;

            $strPageID = $objPage->id;
        }

        $objPageModel = new \PageModel();
        $arrPage = $objPageModel->findPublishedById( $strPageID );

        if ( $arrPage != null ) return $this->generateFrontendUrl( $arrPage->row() );

        return ampersand( \Environment::get('indexFreeRequest') );
    }


    protected function getMethodAttr() {

        return $this->arrForm['method'] ? $this->arrForm['method'] : 'GET';
    }


    public function getResetLink() {

        if ( !$this->arrForm['resetForm'] || $this->arrForm['method'] == 'POST' ) return '';

        return sprintf( '<a href="%s" id="id_form_%s">'. $GLOBALS['TL_LANG']['MSC']['CATALOG_MANAGER']['resetForm'] .'</a>',

            str_replace( '?' . \Environment::get( 'queryString' ), '', \Environment::get( 'requestUri' ) ),
            $this->id
        );
    }


    protected function initialize() {

        if ( !$this->catalogForm ) return false;

        $this->import('CatalogInput');
        $objForm = $this->Database->prepare('SELECT * FROM tl_catalog_form WHERE id = ?')->limit(1)->execute( $this->catalogForm );

        if ( $objForm->numRows ) {

            $this->arrForm = $objForm->row();
            $this->getFormFieldsByParentID( $objForm->id );

            return true;
        }

        return false;
    }


    protected function getFormFieldsByParentID( $strPID ) {

        $objFields = $this->Database->prepare('SELECT * FROM tl_catalog_form_fields WHERE pid = ? AND invisible != "1" ORDER BY sorting')->execute( $strPID );

        if ( $objFields->numRows ) {

            while ( $objFields->next() ) {

                if ( !$objFields->name ) continue;

                $this->arrFormFields[ $objFields->name ] = $objFields->row();
            }
        }
    }


    protected function parseField( $arrField ) {

        if ( $arrField['type'] == 'range' ) {

            $arrField['gtName'] = $arrField['name'] . '_gt';
            $arrField['ltName'] = $arrField['name'] . '_lt';
        }

        $arrField['multiple'] = $arrField['multiple'] ? 'multiple' : '';
        
        if ( in_array( $arrField['type'], [ 'select', 'radio', 'checkbox' ] ) ) {

            $objOptionGetter = new OptionsGetter( $arrField );
            $arrField['options'] = $objOptionGetter->getOptions();
        }

        $arrField['value'] = $this->getActiveOptions( $arrField );
        $arrField['cssID'] = Toolkit::deserialize( $arrField['cssID'] );
        $arrField['cssClass'] = $arrField['cssID'][1] ? $arrField['cssID'][1] . ' ' : '';
        $arrField['onchange'] = $arrField['submitOnChange'] ? 'onchange="this.form.submit()"' : '';
        $arrField['fieldID'] = $arrField['cssID'][0] ? sprintf( 'id="%s"', $arrField['cssID'][0] ) : '';
        $arrField['tabindex'] = $arrField['tabindex'] ? sprintf( 'tabindex="%s"', $arrField['tabindex'] ) : '' ;

        return $arrField;
    }


    protected function getActiveOptions( $arrField ) {

        $strValue = !Toolkit::isEmpty( $arrField['defaultValue'] ) ? \Controller::replaceInsertTags( $arrField['defaultValue'] ) : '';
        $strValue = $this->getInput( $arrField['name'], $strValue );

        if ( $arrField['type'] == 'select' || $arrField['type'] == 'checkbox' ) {

            $arrReturn = [];

            if ( $strValue && ( empty( $arrField['options'] ) || !is_array( $arrField['options'] ) ) ) {

                return $arrReturn;
            }

            if ( !is_array( $strValue ) ) {

                $arrReturn[ $strValue ] = $strValue;

                return $arrReturn;
            }

            return $strValue;
        }

        if ( $arrField['type'] == 'range' ) {

            return [

                'ltValue' => $this->getInput( $arrField['name'] . '_lt' ),
                'gtValue' => $this->getInput( $arrField['name'] . '_gt' )
            ];
        }

        return $strValue;
    }


    protected function getInput( $strFieldname, $strDefault = '' ) {

        if ( !$strFieldname ) return $strDefault;

        return $this->CatalogInput->getActiveValue( $strFieldname );
    }
}