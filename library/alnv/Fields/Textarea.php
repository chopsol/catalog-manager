<?php

namespace CatalogManager;

class Textarea {

    
    public static function generate( $arrDCAField, $arrField ) {

        $arrDCAField['eval']['readonly'] = Toolkit::getBooleanByValue( $arrField['readonly'] );

        if ( $arrField['rte'] ) {

            $arrDCAField['eval']['rte'] = $arrField['rte'];
        }

        if ( $arrField['cols'] ) {

            $arrDCAField['eval']['cols'] = $arrField['cols'];
        }

        if ( $arrField['rows'] ) {

            $arrDCAField['eval']['rows'] = $arrField['rows'];
        }

        if ( $arrField['minlength'] ) {

            $arrDCAField['eval']['minlength'] = intval( $arrField['minlength'] );
        }

        if ( $arrField['maxlength'] ) {

            $arrDCAField['eval']['maxlength'] = intval( $arrField['maxlength'] );
        }

        if ( !$arrDCAField['eval']['tl_class'] ) {

            $arrDCAField['eval']['tl_class'] = 'clr';
        }

        return $arrDCAField;
    }


    public static function parseValue( $varValue, $arrField, $arrCatalog ) {

        if ( !$varValue ) return '';

        return $varValue;
    }
}