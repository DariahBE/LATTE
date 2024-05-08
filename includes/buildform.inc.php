<?php
/**
 * 
 * Class that generates a new form for the user with settings defined by the model. 
 * optionally passed values are added. 
 * 
 * 
 */



 class FormGenerator {

    function __construct($targetPage){
        $formElement = array(); 
        $this->formAction = $targetPage;
        $tokenElement = ''; 
        $submitButton = ''; 
    }

    function add_element($key, $settings, $value){
        //var_dump($settings);
        switch ($settings[1]) {
            case 'int':
                $this->generateIntegerInput($key, $settings, $value);
                break;
            case 'string':
                $this->generateTextInput($key, $settings, $value);
                break;
            case 'bool':
                $this->generateBooleanInput($key, $settings, $value);
                break;
            case 'wikidata': 
                $this->generateWikidataIdInput($key, $settings, $value);
                break;
            case 'uri':
                $this->generateUriInput($key, $settings, $value); 
                break;
            case 'float': 
                $this->generateFloatInput($key, $settings, $value);
            default:
                //invalid types: don't do anything with these.
                break;
        }
    }

    private function idgen($len=4){
        return uniqid('elem') . '_' . bin2hex(random_bytes($len)); 
    }

    //all methods below are called by the constructor!
    public function generateFloatInput($name, $settings, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $settings[2]; 
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique' type='number' step=any name='$name' value='$value'> </textarea>";
    }

    public function generateUriInput($name, $settings, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validate_as_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique' type='url' name='$name' value = '$value'></textarea>"; 
    }
    
    public function generateIntegerInput($name, $settings, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validate_as_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique' type='number' step=1 name='$name' value='$value'> </textarea>";
    }
    
    public function generateBooleanInput($name, $settings, $checked = false) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validate_as_unique' : '';
        $id = $this->idgen(); 
        $checkedAttr = $checked ? "checked" : "";
        $this->formElement[] = "<label for='$id'>$labelname</label> <input id='$id' class='w-full form-control attachValidator $dataunique' type='checkbox' name='$name' $checkedAttr>";
    }
    
    public function generateTextInput($name, $settings, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validate_as_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique' type='text' name='$name' value='$value'> </textarea>";
    }
    
    public function generateWikidataIdInput($name, $settings, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validate_as_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique' type='text' name='$name' pattern='^Q[0-9]+$' value='$value' title='Please enter a Wikidata ID (e.g., Q123)'> </textarea>";
    }

    public function generateHiddenToken($name, $value) {
        //get's called explicitly, not via the constructor!
        $id = $this->idgen(); 
        $this->tokenElement = "<label class='hidden' for='$id'>tkn</label> <input id='$id' type='hidden' name='$name' value='$value'>";
    }

    public function addSubmitButton(){
        $this->submitButton = "<button type='submit' class='btn btn-primary'>Submit</button>";
    }


    function renderForm(){
        $form = "<form action='".$this->formAction."' class='grid gap-6 mb-6 md:grid-cols-2'>";
        foreach($this->formElement as $elem){
            $form.= '<div class="form-group">'.$elem.'</div>'; 
        }
        $form .= '<div class="form-group">'.$this->tokenElement.'</div>'; 
        $form .= $this->submitButton;
        $form .= "</form>";

        //var_dump($form); 
        return $form;
    }

}


?>