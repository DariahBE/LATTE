<?php
/**
 * 
 * Class that generates a new form for the user with settings defined by the model. 
 * optionally passed values are added. 
 * 
 * 
 */



 class FormInputGenerator {

    function __construct($key, $settings, $value){
        var_dump($settings);

        switch ($settings[1]) {
            case 'int':
                # code...
                break;
            case 'string':
                # code...
                break;
            case 'bool':
                # code...
                break;

            default:
                # code...
                break;
        }
    }
    

    public function generateIntegerInput($name, $value = null) {
        return "<input type='number' name='$name' value='$value'>";
    }
    
    public function generateBooleanInput($name, $checked = false) {
        $checkedAttr = $checked ? "checked" : "";
        return "<input type='checkbox' name='$name' $checkedAttr>";
    }
    
    public function generateTextInput($name, $value = null) {
        return "<input type='text' name='$name' value='$value'>";
    }
    
    public function generateWikidataIdInput($name, $value = null) {
        return "<input type='text' name='$name' pattern='^Q[0-9]+$' value='$value' title='Please enter a Wikidata ID (e.g., Q123)'>";
    }

    public function generateHiddenToken($name, $value) {
        return "<input type='hidden' name='$name' value='$value'>";
    }



}


?>