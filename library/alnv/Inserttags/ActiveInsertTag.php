<?php

namespace CatalogManager;

class ActiveInsertTag extends \Frontend {


    public function __construct() {

        $this->import('CatalogInput');
    }


    public function getInsertTagValue( $strTag ) {

        $arrTags = explode( '::', $strTag );

        if ( is_array( $arrTags ) && $arrTags[0] == 'CTLG_ACTIVE' && isset( $arrTags[1] ) ) {

            global $objPage;

            $varValue =  $this->CatalogInput->getActiveValue( $arrTags[1] );

            if ( isset( $arrTags[2] ) && strpos( $arrTags[2], '?' ) !== false ) {

                $arrChunks = explode('?', urldecode( $arrTags[2] ), 2 );
                $strSource = \StringUtil::decodeEntities( $arrChunks[1] );
                $strSource = str_replace( '[&]', '&', $strSource );
                $arrParams = explode( '&', $strSource );

                $blnIsDate = false;
                $strDateFormat = $objPage->dateFormat;

                foreach ( $arrParams as $strParam ) {

                    list( $strKey, $strOption ) = explode( '=', $strParam );

                    switch ( $strKey ) {

                        case 'default':

                            $varValue = $strOption;

                            break;

                        case 'isDate':

                            $blnIsDate = true;

                            break;

                        case 'dateFormat':

                            $strDateFormat = $strOption;

                            break;
                    }
                }

                if ( $blnIsDate && is_array( $varValue ) ) {

                    foreach ( $varValue as $strK => $strV ) {

                        if ( !$strV ) continue;

                        $objDate = new \Date( $strV, $strDateFormat );
                        $varValue[ $strK ] = $objDate->tstamp;
                    }
                }

                if ( $blnIsDate && is_string( $varValue ) ) {

                    $objDate = new \Date( $varValue, $strDateFormat );
                    $varValue = $objDate->tstamp;
                }
            }

            elseif( !$varValue ) {

                $varValue = $arrTags[2] ? $arrTags[2] : '';
            }

            if ( is_array( $varValue ) ) $varValue = implode( ',', $varValue );

            return $varValue;
        }

        return false;
    }
}