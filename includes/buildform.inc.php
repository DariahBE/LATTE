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
        $this->formElement = array(); 
        $this->formAction = $targetPage;
        $this->nodeAttrs = array(); 
        $tokenElement = ''; 
        $submitButton = ''; 
    }

    function add_element($key, $settings, $value, $border = False, $fill = False){
        switch ($settings[1]) {
            case 'int':
                $this->generateIntegerInput($key, $settings, $border, $fill, $value);
                break;
            case 'string':
                $this->generateTextInput($key, $settings, $border, $fill, $value);
                break;
            case 'bool':
                $this->generateBooleanInput($key, $settings, $border, $fill, $value);
                break;
            case 'wikidata': 
                $this->generateWikidataIdInput($key, $settings, $border, $fill, $value);
                break;
            case 'uri':
                $this->generateUriInput($key, $settings, $border, $fill, $value); 
                break;
            case 'float': 
                $this->generateFloatInput($key, $settings, $border, $fill, $value);
            default:
                //invalid types: don't do anything with these.
                break;
        }
    }

    private function idgen($len=4){
        return uniqid('elem') . '_' . bin2hex(random_bytes($len)); 
    }

    //all methods below are called by the constructor!
    public function generateFloatInput($name, $settings,$border, $fill, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $settings[2]; 
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique $border $fill' type='number' step=any name='$name'>".htmlspecialchars($value)."</textarea>";
    }

    public function generateUriInput($name, $settings, $border, $fill, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validateAs_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique $border $fill' type='url' name='$name'>".htmlspecialchars($value)."</textarea>"; 
    }
    
    public function generateIntegerInput($name, $settings, $border, $fill, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validateAs_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique $border $fill' type='number' step=1 name='$name'>".htmlspecialchars($value)."</textarea>";
    }
    
    public function generateBooleanInput($name, $settings, $border, $fill, $checked = false) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validateAs_unique' : '';
        $id = $this->idgen(); 
        $checkedAttr = $checked ? "checked" : "";
        $this->formElement[] = "<label for='$id'>$labelname</label> <input id='$id' class='w-full form-control attachValidator $dataunique $border $fill' type='checkbox' name='$name' $checkedAttr>";
    }
    
    public function generateTextInput($name, $settings, $border, $fill, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validateAs_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique $border $fill' type='text' name='$name' >".htmlspecialchars($value)."</textarea>";
    }
    
    public function generateWikidataIdInput($name, $settings, $border, $fill, $value = null) {
        $labelname = $settings[0]; 
        $datatype = $settings[1]; 
        $dataunique = $dataunique = ($settings[2] === true) ? 'validateAs_unique' : '';
        $id = $this->idgen(); 
        $this->formElement[] = "<label for='$id'>$labelname</label> <textarea id='$id' class='w-full form-control attachValidator $dataunique $border $fill' type='text' name='$name' pattern='^Q[0-9]+$' title='Please enter a Wikidata ID (e.g., Q123)'>".htmlspecialchars($value)."</textarea>";
    }

    public function generateHiddenToken($name, $value) {
        //get's called explicitly, not via the constructor!
        $id = $this->idgen(); 
        $this->tokenElement = "<label class='hidden' for='$id'>tkn</label> <input id='$id' type='hidden' name='$name' value='$value'>";
    }

    public function generateNodeFieldattributes($label, $neoid){
        //generates a post field which is hidden to pass down the nodelabel. 
        //passes down the LABEL (str) and NEO4J ID (int) of the updated node. 
        //ALWAYS like this. 
        $id = $this->idgen(); 
        $this->nodeAttrs[] = "<label class='hidden' for='$id'>label</label> <input id='$id' type='hidden' name='app_logic_db_label' value='$label'>"; 
        $id = $this->idgen(); 
        $this->nodeAttrs[] = "<label class='hidden' for='$id'>nid</label> <input id='$id' type='hidden' name='app_logic_db_neoid' value='".(int)$neoid."'>"; 
    }

    public function addSubmitButton(){
        // bg-green-500 border-solid hover:bg-green-600 p-2 m-2 rounded-lg text-white font-bold
        $this->submitButton = "<button type='submit' class='btn btn-primary bg-green-500 border-solid hover:bg-green-600 p-2 m-2 rounded-lg text-white font-bold'>Submit</button>";
    }


    function renderForm(){
        $form = "<form action='".$this->formAction."'  method='POST' class='grid gap-6 mb-6 md:grid-cols-2'>";
        foreach($this->formElement as $elem){
            $form.= '<div class="form-group">'.$elem.'</div>'; 
        }
        $form .= '<div class="form-group">'.$this->tokenElement.'</div>'; 
        if(boolval($this->nodeAttrs)){
            //always contains two elements, so this is an acceptable solution. 
            $form .= '<div class="form-group">'.$this->nodeAttrs[0].'</div>'; 
            $form .= '<div class="form-group">'.$this->nodeAttrs[1].'</div>'; 
        }
        $form .= $this->submitButton;
        $form .= "</form>";

        //var_dump($form); 
        return $form;
    }

}


?>