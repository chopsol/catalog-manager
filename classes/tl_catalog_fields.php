<?php

namespace CatalogManager;

class tl_catalog_fields extends \Backend {

    public function createFieldOnSubmit( \DataContainer $dc ) {

        $strID = $dc->activeRecord->pid;
        $objSQLBuilder = new SQLBuilder();
        $strIndex = $dc->activeRecord->useIndex;
        $strStatement = DCABuilder::$arrSQLStatements[ $dc->activeRecord->statement ];
        $arrCatalog = $this->Database->prepare('SELECT * FROM tl_catalog WHERE id = ? LIMIT 1')->execute( $strID )->row();
        
        if ( !$this->Database->fieldExists( $dc->activeRecord->fieldname, $arrCatalog['tablename'] ) ) {

            if ( in_array( $dc->activeRecord->type , DCABuilder::$arrForbiddenInputTypesMap ) ) {

                return null;
            }

            $objSQLBuilder->alterTableField( $arrCatalog['tablename'], $dc->activeRecord->fieldname, $strStatement );

            if ( $strIndex ) {

                $objSQLBuilder->addIndex( $arrCatalog['tablename'], $dc->activeRecord->fieldname, $strIndex );
            }
        }

        else {
            
            if ( in_array( $dc->activeRecord->type , DCABuilder::$arrForbiddenInputTypesMap ) ) {

                $this->dropFieldOnDelete( $dc );

                return null;
            }

            $arrColumns = $objSQLBuilder->showColumns( $arrCatalog['tablename'] );

            if ( !$arrColumns[ $dc->activeRecord->fieldname ] ) {

                return null;
            }

            if ( $arrColumns[ $dc->activeRecord->fieldname ]['statement'] !== $strStatement ) {

                $objSQLBuilder->modifyTableField( $arrCatalog['tablename'], $dc->activeRecord->fieldname, $strStatement );
            }

            if ( $strIndex && $strIndex !== $arrColumns[ $dc->activeRecord->fieldname ]['index'] ) {

                $objSQLBuilder->dropIndex( $arrCatalog['tablename'], $dc->activeRecord->fieldname );
                $objSQLBuilder->addIndex( $arrCatalog['tablename'], $dc->activeRecord->fieldname, $strIndex );
            }
        }
    }

    public function dropFieldOnDelete( \DataContainer $dc ) {

        $strID = $dc->activeRecord->pid;
        $arrCatalog = $this->Database->prepare('SELECT * FROM tl_catalog WHERE id = ? LIMIT 1')->execute( $strID )->row();

        $objSQLBuilder = new SQLBuilder();
        $objSQLBuilder->dropTableField( $arrCatalog['tablename'], $dc->activeRecord->fieldname );
    }

    public function getFieldTypes() {

        return [

            'text',
            'date',
            'radio',
            'hidden',
            'number',
            'select',
            'upload',
            'message',
            'checkbox',
            'textarea',
            'fieldsetStart',
            'fieldsetStop'
        ];
    }

    public function getIndexes() {

        return [ 'index', 'unique' ];
    }

    public function getRGXPTypes( \DataContainer $dc ) {

        if ( $dc->activeRecord->type && $dc->activeRecord->type == 'number') {

            return [ 'digit', 'natural', 'prcnt' ];
        }

        if ( $dc->activeRecord->type && $dc->activeRecord->type == 'date') {

            return [ 'date', 'time', 'datim' ];
        }

        return [

            'url',
            'time',
            'date',
            'alias',
            'alnum',
            'alpha',
            'datim',
            'digit',
            'email',
            'extnd',
            'phone',
            'prcnt',
            'locale',
            'emails',
            'natural',
            'friendly',
            'language',
            'folderalias',
        ];
    }

    public function getRichTextEditor() {

        return [

            'tinyMCE',
            'tinyFlash'
        ];
    }

    public function getSQLStatements() {

        return DCABuilder::$arrSQLStatements;
    }

    public function getCatalogFieldList( $arrRow ) {

        return $arrRow['title'];
    }
}