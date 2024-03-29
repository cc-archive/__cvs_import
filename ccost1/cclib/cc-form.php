<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


/**
 * Base class for all HTML forms in the system.
 * 
 * Extend this class for basic forms that do not have any uploading
 * needs. It contains several field type handlers built in such as
 * text inputs, textareas, radio groups, checkboxes, etc.
 *
 */
class CCForm 
{
    var $_template_vars;
    var $_form_fields;
    var $_template_macro;

    /**
     * Constructor
     *
     * This method sets up several defaults (submit button text, form method) but also
     * creates several hidden fields on the form:
     * 
     * <b>'http_referer'</b> remembers the URL this form was originally called 
     * from. 
     * <b>-classname-</b> remembers the name of the class that created this form 
     * minus the 'cc' prefix and 'form' postfix. e.g. if the name of this class is 
     * CCMyEditingForm the name of this field is 'myediting'. It's value is always 
     * the string 'classname'. You can use this field to confirm at POST time that 
     * you are processing the right form:
     * 
     * <code>

          if( !empty($_POST['myediting']) )
          {
                 // .... User hit 'submit' button
          }
     * </code>
     *
     */
    function CCForm()
    {
        $this->_form_fields = array();

        $this->_template_vars = array(
                                'form_method' => 'post',
                                'submit_text' => "Submit",
                                );

        $this->_template_macro = 'html_form';

        if( array_key_exists('HTTP_REFERER',$_SERVER) )
        {
            $this->SetHiddenField( 'http_referer', 
                                   htmlspecialchars(urlencode($_SERVER['HTTP_REFERER'])),
                                   CCFF_HIDDEN | CCFF_NOUPDATE );
        }

        // it's conceivable that REQUEST_URI might work here...
        $this->SetHandler( $_SERVER['REQUEST_URI'] );

        $p1 = substr(get_class($this),2);
        $this->SetHiddenField( substr($p1,0,strlen($p1)-4), 'classname', CCFF_HIDDEN | CCFF_NOUPDATE );
    }

    /**
     * Set the value of an html form field. 
     *
     * @param  string $fieldname The form field's name
     * @param  mixed  $value     Value for the form field. The type depends on the type of html form field. For example a textedit formatter expects text, a choice field expects an array.
     */
    function SetFormValue( $fieldname, $value )
    {
        $this->SetFormFieldItem( $fieldname, 'value', $value );
    }

    /**
     * Get the current value in a form field.
     *
     * @param  string $fieldname The form field's name
     * @return      The value. The type of the value depends on the type of field.
     */
    function GetFormValue( $fieldname )
    {
        return( $this->GetFormFieldItem($fieldname,'value') );
    }

    /**
     * Returns an array of all the fields in the table.
     *
     * This will skip fields marked static or no-update. The array returned can be used "as is" for CCTable functions Insert and Update.
     *
     * @param  array $values A reference to an array that receives the values.
     */
    function GetFormValues( &$values )
    {
        foreach( $this->_form_fields as $name => $ff ) 
            if( $this->_should_update($ff) )
                $values[$name] = $ff['value'];
    }


    /**
     * Creates a hidden form field and sets it's value
     *
     * @access public
     * @param  string $name The form element name
     * @param  string $value Value to to be used 
     * @param  integer $flags CCFF_* flags 
     */
    function SetHiddenField( $name, $value, $flags = CCFF_HIDDEN_DEFAULT )
    {
        $this->_form_fields[$name] = array(  'flags' => $flags,
                                             'value' => $value );
    }

    /**
    * Add meta-data for HTML form fields
    * 
    * The meta data is an array of structures that describes the field<br />
    * and determines the behavior of CCForm methods.
    *  
    * Typical usage is to call this method from the constructor of a form and looks like:
    * 
    * <code>
    
        $fields = array(

            'contest_friendly_name' => array (
                        'label'      => 'Friendly Name',
                        'form_tip'   => 'This is the one people actually see',
                        'formatter'  => 'textedit',
                        'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

            'contest_description' => array (
                        'label'      => 'Description',
                        'form_tip'   => 'Let people know that this contest is about',
                        'formatter'  => 'textarea',
                        'flags'      => CCFF_POPULATE),
               );

        $this->AddFormFields( $fields );
    
    * </code>
    * 
    * The name ('contest_friendly_name' above) will be the name and id of the HTML form
    * element and can be used in $_POST although all that is encapsulated in this class.
    * 
    * The various fields are described below:
    *<table class="listing_table">
    <tr><th>Element</th><th>Type</th><th>Descpription</th></tr>

    <tr><td class="h">label</td><td>string</td><td>The main text label for the field</td></tr>
    <tr><td class="h">form_tip</td><td>string</td><td>Helpful text used to further describe, preferrably by example, the field</td></tr>
    <tr><td class="h">formatter</td><td>string</td>
                 <td>This name will map into two functions in the CCForm (or derived class). One has a 'generator_' prefix,
                    the other has a 'validator_' prefix. For example, if the formatter value here is 'textedit', that means
                    that there are two methods on this class 'generator_textedit' and 'validator_textedit'. The generator
                    is used when HTML need to be generated, the validator is used on POST to validate if the user has
                    entered valid data. <br /><br />CCForm has many stock formatters for standard INPUT fields but the formatter
                    can be a completely unique value as long there are matching methods in the level of derivation (or above).
                    For example if the formatter is 'unique_name' you can write a generator_unique_name() method that simply
                    calls the generator_textedit() and a validator_unique_name() method that checks against a database to
                    ensure the value is unique to the database before accepting the form.<br /><br />This field is required
                    unless it is hidden.
                 </td></tr>
    <tr><td class="h">flags</td><td>integer</td>
                 <td>Control flags for how to treat this field during various stags of processing. here are possible values:
                  <table class="listing_table">
                     <tr><th>Flag</th><th>Used by method</th><th>Description</th></tr>
                     <tr><td>CCFF_NONE</td><td></td><td>Just do all default behavior</td></tr>
                     <tr><td>CCFF_SKIPIFNULL</td><td>GetFormValues</td><td>Do not return this field if blank(good for passwords left blank)</td></tr>
                     <tr><td>CCFF_NOUPDATE</td><td>GetFormValues</td><td>Never return this field (good for static and hidden)</td></tr>
                     <tr><td>CCFF_POPULATE</td><td>PopulateValues</td><td>If this flag is set, it will use the matching value
                        passed in to PopulateValues, otherwise this field will be left alone during that process.</td></tr>
                     <tr><td>CCFF_HIDDEN</td><td>GenerateForm</td><td>Creates a type='hidden' INPUT field, you can also use the
                              SetHiddenField() method.</td></tr>
                     <tr><td>CCFF_REQUIRED</td><td>validator_must_exist</td><td>validator_* methods call validator_must_exist()
                        and if this flag is present it will return 'false'</td></tr>
                     <tr><td>CCFF_NOSTRIP</td><td>ValidateFields</td><td>The default behavoir is to strip all tags
                       out of all input values. If that flag is present the strip is skipped.</td></tr>
                     <tr><td>CCFF_NOADMINSTRIP</td><td>ValidateFields</td><td>Behaves the same as CCFF_NOSTRIP but only if the current
                       user is logged in an admin.</td></tr>
                     <tr><td>CCFF_STATIC</td><td>ValidateFields</td><td>No validation will be done this field.</td></tr>
                     <tr><td>CCFF_HIDDEN_DEFAULT</td><td></td><td>Combination of CCFF_HIDDEN | CCFF_POPULATE</td></tr></table>
                  </td></tr>
     <tr><td class="h">value</td><td>mixed</td><td>A default value used by the generator.</td></tr>
     <tr><td class="h">options</td><td>array</td><td>Applies to multiple choice formatters (e.g. radio, select).
<pre>
    'formatter'  => 'radio',
    'options'      => array( 
                        '0' => 'Winner is determined offline',
                        '1' => 'Display a poll after deadline for entries has passed' )
</pre>
</td></tr>
     <tr><td class="h">class</td><td>string</td><td>Name of css class to use. This is rare since most skins will style the INPUT fields
     using generic selectors in the style sheet, however 'cc_form_input_short' is used when a smaller text input field is desired.</td></tr>
     <tr><td class="h">maxwidth</td><td>integer</td><td>Used by the 'avatar' formatter for sizing the image.</td></tr>
     <tr><td class="h">maxheight</td><td>integer</td><td>Used by the 'avatar' formatter for sizing the image.</td></tr>
     <tr><td class="h">macro</td><td>string</td><td>Used by the 'metalmacro' formatter and refers to a template macro
       to use form formatting the output for this field.  
<pre>
        'upload_license' =>
                    array( 'label'      => 'License',
                           'formatter'  => 'metalmacro',
                           'macro'      => 'license.xml/license_choice',
                           'flags'      => CCFF_POPULATE,
                           'license_choices' => $lics
                    )
</pre>
        In this case 'license_choices' is a value expected by the macro 'license_choice' in the file 'license.xml'.
       </td></tr>
    <tr><td class="h">nomd5</td><td>boolean</td><td>Used by the 'password' formatter which normally would hash the input
    value using md5(). If this flag is present and set to 'true' the value is not hashed. </td></tr>
                     
    </table>
    * 
    * As you can see there are a core set of elements ('label', 'form_tip', 'value', etc.) while others are specific for
    * various formatters. Obviously, any formatter pair of generator/validator functions can speficy any name/value pair
    * in the element structure.
    *
    * @param array $fields Array of meta-data structures.
    */
    function AddFormFields( &$fields )
    {
        // the += operator will NOT overwrite existing keys with new
        // information:
        // http://us4.php.net/manual/en/language.operators.array.php
        $this->_form_fields = array_merge($this->_form_fields,$fields);
    }

    /**
    * Set the 'action' field of the form element. (The default is the current url.)
    *
    * @param string $handler URL of the post url.
    */
    function SetHandler( $handler )
    {
        $this->_template_vars['form_action'] = $handler;
    }

    /**
    * Set the text for the submit button. (Set to '' to remove the button from the form.)
    *
    * @param string $text Value for submit button.
    */
    function SetSubmitText($text)
    {
        if( empty($text) )
        {
            if( array_key_exists('submit_text',$this->_template_vars ) )
            {
                unset($this->_template_vars['submit_text']);
            }
        }
        else
        {
            $this->_template_vars['submit_text'] = $text;
        }
    }

    /**
    * Puts up a helper caption text above the form.
    *
    * @param string $text Value for helper text.
    */
    function SetHelpText($text)
    {
        $this->CallFormMacro('form_about','form_about',$text);
    }

    /**
    * Sets error text when a validator has failed on a given field.
    *
    * This method is called from validators. If the form is generated and shown this text is output directly
    * above the offending field.
    *
    * @param string $fieldname Name passed into AddFormFields for the field
    * @param string $errmsg Text to display to user
    */
    function SetFieldError($fieldname,$errmsg)
    {
        $this->SetFormFieldItem($fieldname,'form_error',$errmsg);
    }

    /**
    * Checks the existance of a field in the form
    *
    * @param string $name Name passed into AddFormFields for the field
    */
    function FormFieldExists( $name )
    {
        return( array_key_exists($name,$this->_form_fields) );
    }

    /**
    * Retrieves a specific element from the field's meta-data structure.
    *
    * @see CCForm::AddFormFields
    * @param string $fieldname Name passed into AddFormFields for the field
    * @param string $itemname Name of the element to retrieve.
    */
    function GetFormFieldItem( $fieldname, $itemname )
    {
        $field_info =& $this->_get_form_field($fieldname);
        if( empty($field_info[$itemname]) )
            return(null);
        $value = $field_info[$itemname];
        return( $value );
    }

    /**
    * Set the value for a specific element in a field's meta-data structure.
    *
    * @see CCForm::AddFormFields
    * @param string $fieldname Name passed into AddFormFields for the field
    * @param string $itemname Name of the element to retrieve.
    * @param mixed $value Value to put into structure
    */
    function SetFormFieldItem( $fieldname, $itemname, $value )
    {
        $field_info =& $this->_get_form_field($fieldname);
        $field_info[$itemname] = $value;
    }

    /**
    * Set (or replace) a field's meta-data structure.
    *
    * @see CCForm::AddFormFields
    * @param string $name  Name of HTML field
    * @param mixed $value Meta-data structure for field
    */
    function AddFormField( $name, $value )
    {
        $this->_form_fields[$name] = $value;
    }

    /**
    * Set (or replace) a template field to be passed to the template generator
    *
    * This method is used when specific adornments to the form are needed (e.g.
    * remix search box).
    *
    * @param string $name Name of template field
    * @param mixed $value Data for template processor
    */
    function SetTemplateVar($name,$value)
    {
        $this->_template_vars[$name] = $value;
    }

    /**
    * Get template data to be passed to the template generator
    *
    * This method is used when specific adornments to the form are needed (e.g.
    * remix search box).
    *
    * @param string $name Name of template field
    * @returns mixed $value Data posted back to form for this element
    */
    function GetTemplateVar($name)
    {
        return($this->_template_vars[$name]);
    }

    /**
    * Get all template vars for this form (used by page generator)
    *
    * @returns array $vars Template vars to be passed to template generator
    */
    function GetTemplateVars()
    {
        return( $this->_template_vars );
    }

    /**
    * Test if a specific form adornment template exists.
    *
    * This method is used when specific adornments to the form are needed (e.g.
    * remix search box).
    *
    * @param string $name Name of template field
    * @returns boolean $bool true if element exists
    */
    function TemplateVarExists($name)
    {
        return( array_key_exists($name,$this->_template_vars) );
    }

    /**
    * Set (or replace) a a group of template fields to be passed to the template generator
    *
    * This method is used when specific adornments to the form are needed (e.g.
    * licensing methods).
    *
    * @param array $value Data for template processor
    */
    function AddTemplateVars($value)
    {
        $this->_template_vars = array_merge($this->_template_vars, $value );
    }

    /**
    * Sets up for custom macros to be called when generating the form
    *
    * This method is used when specific adornments to the form are needed (e.g.
    * remix search box).
    *
    * @param string $data_label The name for the record expected by the macro
    * @param string $macro_name The name of the template macro
    * @param mixed  $value      Value to be assigned to $data_label
    */
    function CallFormMacro($data_label,$macro_name,$value=true)
    {
        $this->_template_vars[$data_label] = $value;
        $this->_template_vars['form_macros'][] = $macro_name;
    }

    /**
    * Determines the main template macro to be used for this form (default is 'html_form')
    *
    * @param string $macro_name The name of the template macro
    */
    function SetTemplateMacro($macro_name)
    {
        $this->_template_macro = $macro_name;
    }

    /**
    * Retrieves the main template macro to be used for this form (default is 'html_form')
    *
    * @returns string $macro_name The name of the template macro
    */
    function GetTemplateMacro()
    {
        return( $this->_template_macro );
    }


    /**
     * Prepares form variables for display.
     * 
     * Generates template arrays. Returns $this to make it easy to add to pages.
     * 
     * <code>
     *    $page->AddForm( $form->GenerateForm() );
     *</code>
     *  
     * @see CCForm::AddFormFields
     * @returns array $varsname Array containing two elements: array of variables and template marco name
    */
    function GenerateForm($hiddenonly = false)
    {
        $this->_template_vars['html_form_fields']   = array();
        $this->_template_vars['html_hidden_fields'] = array();

        $fieldnames = array_keys($this->_form_fields);
        foreach(  $fieldnames as $fieldname )
        {
            $form_fields =& $this->_get_form_field($fieldname);

            if( $form_fields['flags'] & CCFF_HIDDEN )
            {
                $this->_template_vars['html_hidden_fields'][] = 
                        array( 'hidden_name' => $fieldname,
                               'hidden_value' => $form_fields['value']);
            }
            else
            {
                if( $hiddenonly )
                    continue;

                $generator  = 'generator_' . $form_fields['formatter'];
                if( ($form_fields['formatter'] != 'password') && isset($form_fields['value']) )
                    $value = $form_fields['value'];
                else
                    $value = '';
                $class = empty($form_fields['class']) ? '' : $form_fields['class'];
                if( !empty($form_fields['form_error']) ) // EVIL:
                    $class .= "\" class='cc_form_error_input' ";

                $form_fields['form_element'] = $this->$generator( $fieldname, $value, $class );
                $this->_template_vars['html_form_fields'][] = $form_fields;
            }
        }

        $id = strtolower(substr(get_class($this),2));
        $this->_template_vars['form_id'] = $id;

        return( $this );
    }

    /**
     * Validates the fields in this form, called during POST processing.
     * 
     * @see CCForm::AddFormFields
     * @returns bool $success true if all fields validated, false on errors
    */
    function ValidateFields()
    {
        $retval   = true;
        $nostrip = CCUser::IsAdmin() ? CCFF_NOADMINSTRIP : 0;
        $nostrip |= CCFF_NOSTRIP;

        $fieldnames = array_keys($this->_form_fields);
        foreach(  $fieldnames as $fieldname )
        {
            $form_fields =& $this->_get_form_field($fieldname);
            $flags = $form_fields['flags'];
            if( $flags & CCFF_STATIC )
                continue;

            $value = $this->_fetch_post_value($fieldname); 

            if( !empty($value) && !is_array($value) )
            {
                if( CCUser::IsAdmin() ) // $flags & $nostrip )
                    CCUtil::StripSlash($value);
                else
                    CCUtil::StripText($value);
            }

            $this->_form_fields[$fieldname]['value'] = $value;
            if( !($flags & CCFF_HIDDEN) )
            {
                $validator = 'validator_' . $form_fields['formatter'];
                $ret = $this->$validator($fieldname);
                $retval = $retval && $ret;
            }
        }

        return( $retval );
    }

    /**
     * Populate the values of the field with specific data (e.g. a database record)
     * 
     * @see CCForm::AddFormFields
     * @param array $values Name/value pairs to be used to populate fields
    */
    function PopulateValues($values)
    {

        $keys = array_keys($this->_form_fields);
        foreach( $keys as $fieldname )
        {
            $F =& $this->_form_fields[$fieldname];
            if( $F['flags'] & CCFF_POPULATE )
            {
                $F['value'] = empty($values[$fieldname]) ? '' : $values[$fieldname];
            }
        }
    }


    // ----------------------------------------------------------------
    //
    //   Internal helpers 
    //
    // ----------------------------------------------------------------

    function & _get_form_field( $name )
    {
        return( $this->_form_fields[$name] );
    }

    function _should_update(&$form_fields)
    {
        $flags = $form_fields['flags'];

        if( ($flags & CCFF_NOUPDATE) ||
               (empty($form_fields['value']) && ($flags & CCFF_SKIPIFNULL)) 
           )
        {
            return(false);
        }

        return(true);
    }

    // this method is overridden in base classes for array cases
    function _fetch_post_value($name)
    {
        if( !array_key_exists($name,$_POST) )
            return( null );

        $value = $_POST[$name];
        return( $value );
    }

    // ----------------------------------------------------------------
    //
    //   Helpers for html generators and validators 
    //
    // ----------------------------------------------------------------

    function validator_must_exist($fieldname)
    {
        $flags = $this->GetFormFieldItem($fieldname,'flags');
        $value = $this->GetFormValue($fieldname);
        if( ($flags & CCFF_REQUIRED) && empty($value) )
        {
            $this->SetFieldError( $fieldname, "can not be left blank" );
            return(false);
        }
        return( true );
    }

    // ----------------------------------------------------------------
    //
    //   Standard html generators and validators below
    //
    // ----------------------------------------------------------------

    /**
     * Handles generation of HTML field (actually a NOP)
     *
     * @param string $varname (ignored)
     * @param string $value   (ignored)
     * @param string $class   (ignored)
     * @returns string $html (empty)
     */
    function generator_metalmacro($varname,$value='',$class='')
    {
        return( '' );
    }

    function validator_metalmacro($fieldname)
    {
        return( true );
    }

    /**
     * Handles generation of HTML field &lt;input type='text'
     *
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_textedit($varname,$value='',$class='')
    {
        return( "<input type='text' id=\"$varname\" name=\"$varname\" value=\"$value\" class=\"$class\" />" );
    }

    function validator_textedit($fieldname)
    {
        return( $this->validator_must_exist($fieldname) );
    }

    /**
     * Handles generation of HTML field &lt;span
     *
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_statictext($varname,$value='',$class='')
    {
        return( "<span class=\"$class\">$value</span>" );
    }

    function validator_statictext($fieldname)
    {
        return( true );
    }

    /**
     * Handles generation of HTML field &lt;textarea
     *
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_textarea($varname,$value='',$class='')
    {
        return( "<textarea id=\"$varname\" name=\"$varname\" class=\"$class\">$value</textarea>" );
    }

    function validator_textarea($fieldname)
    {
        return( $this->validator_must_exist($fieldname) );
    }

    /**
     * Handles generation of HTML field &lt;input type='checkbox'
     *
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_checkbox($varname,$value='',$class='')
    {
        if( !empty( $value ) )
            $value = 'checked = "checked" ';
        else
            $value ='';

        return( "<input type=\"checkbox\" id=\"$varname\" name=\"$varname\" $value class=\"$class\" />" );
    }

    function validator_checkbox($fieldname)
    {
        $value = $this->GetFormValue($fieldname);
        $this->SetFormValue( $fieldname, isset($value) ? 1 : 0 );
        return( true );
    }

    /**
     * Handles generation of several &lt;input type='radio' HTML field 
     * 
     * The 'options' field for the field descriptor must be an array
     * of options to be generated here
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_radio($varname,$value=null,$class='')
    {
        $options =& $this->GetFormFieldItem($varname,'options');
        $html = '';
        foreach( $options as $ovalue => $otext )
        {
            if( !isset($value) )
                $value = $ovalue;

            if( $value == $ovalue )
                $selected = 'checked="checked" ';
            else
                $selected = '';

            $html .= "<input type=\"radio\" id=\"$varname\" name=\"$varname\" value=\"$ovalue\" ".
                    "$selected class=\"$class\" /><label>$otext</label><br />" ;
        }

        return($html);
    }

    function validator_radio($fieldname)
    {
        return( true );
    }


    /**
     * Handles generation &lt;select and several &lt;option HTML field 
     * 
     * The 'options' field for the field descriptor must be an array
     * of options to be generated here
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_select($varname,$value='',$class='')
    {
        $options = $this->GetFormFieldItem($varname,'options');
        $fvalue   = $this->GetFormValue($varname);
        $html = "<select id=\"$varname\" name=\"$varname\" class=\"$class\">";
        foreach( $options as $value => $text )
        {
            if( $value == $fvalue )
                $selected = ' selected="selected" ';
            else
                $selected = '';

            $html .= "<option value=\"$value\" $selected >$text</option>";
        }
        $html .= "</select>";
        return( $html );
    }

    function validator_select($fieldname)
    {
        return(true);
    }

    /**
     * Handles generation of &lt;input type='password' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_password($varname,$value='',$class='')
    {
        return( "<input type=\"password\" id=\"$varname\" name=\"$varname\" value=\"$value\" class=\"$class\" />" );
    }

    function validator_password($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);

            if( empty( $value ) )
                return( true );

            if( strlen($value) < 5 )
            {
                $this->SetFieldError($fieldname," must be at least 5 characters");
                return(false);
            }
            if( preg_match('/[^A-Za-z0-9]/', $value) )
            {
                $this->SetFieldError($fieldname, " must letters or numbers");
                return(false);
            }

            $nomd5 = $this->GetFormFieldItem( $fieldname, 'nomd5' );
            if( !$nomd5 )
                $value = md5($value);
            $this->SetFormValue( $fieldname, $value );

            return( true );
        }

        return( false );
    }

    /**
     * Handles generation of &lt;input type='text' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_email($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }

    function validator_email($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);

            $regex = "/^[A-Z0-9]+([\._\-A-Z0-9]+)?@[A-Z0-9\.\-_]+\.{1}[A-Z0-9\-_]{2,7}$/i";

            if( !preg_match( $regex, $value ) )
            {
                $this->SetFieldError($fieldname,"Not a valid email address");
                return(false);
            }

            return( true );
        }
        return( false );
    }

    /**
     * Handles generation of &lt;input type='text' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_tagsedit($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }

    function validator_tagsedit($fieldname)
    {
        $value = $this->GetFormValue($fieldname);
        $tags =& CCTags::GetTable();
        $value = $tags->PrepForDB($value);
        $this->SetFormValue($fieldname,$value);

        return( $this->validator_must_exist($fieldname) );
    }

    /**
     * Handles generation a bunch of &lt;select HTML fields that represents a date/time
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_date($varname,$value='',$class='')
    {
        if( empty($value) )
            $value = date('Y-m-d H:i');

        $time = strtotime($value);

        $html = "<div class=\"cc_date_field\"><select name=\"$varname" . "[m]\"  id=\"$varname" . "[m]\" >";
        $month = date('F',$time);
        for( $i = 1; $i < 13; $i++ )
        {
            $m = date('F', mktime(0,0,0,$i));
            $selected = $month == $m ? 'selected="selected"' : '';
            $html .= "<option $selected value=\"$m\">$m</option>";
        }
        $html .= "</select>\n";

        $today = date("d",$time);
        $html .= "<select name=\"$varname" . "[d]\"  id=\"$varname" . "[d]\" >";
        for( $i = 1; $i < 32; $i++ )
        {
            if( $i == $today )
                $selected = ' selected = "selected" ';
            else
                $selected = '';

            $html .= "<option value=\"$i\" $selected>$i</option>";
        }
        $html .= "</select>\n";

        $html .= "<select name=\"$varname" . "[y]\"  id=\"$varname" . "[y]\" >";
        $y = date("Y",$time);
        for( $i = 0; $i < 5; $i++, $y++ )
        {
            $html .= "<option value=\"$y\">$y</option>";
        }
        $html .= "</select>\n";

        $html .= ' - ';


        $hour = date("h",$time);
        $html .= "<select name=\"$varname" . "[h]\"  id=\"$varname" . "[h]\" >";
        for( $i = 1; $i < 13; $i++ )
        {
            if( $i == $hour )
                $selected = ' selected = "selected" ';
            else
                $selected = '';

            $html .= "<option value=\"$i\" $selected>$i</option>";
        }
        $html .= "</select>";

        $html .= ':';

        $minute = date("i",$time);
        $html .= "<select name=\"$varname" . "[i]\"  id=\"$varname" . "[i]\" >";
        for( $i = 0; $i < 60; $i++ )
        {
            if( $i == $minute )
                $selected = ' selected = "selected" ';
            else
                $selected = '';

            if( $i < 10 )
                $it = "0$i";
            else
                $it = $i;

            $html .= "<option value=\"$it\" $selected>$it</option>";
        }
        $html .= "</select>";


        $ampm = date("a",$time);
        $html .= "<select name=\"$varname" . "[a]\"  id=\"$varname" . "[a]\" >";
        $apa = array( "am", "pm" );
        foreach( $apa as $apc )
        {
            if( $apc == $ampm)
                $selected = ' selected = "selected" ';
            else
                $selected = '';

            $html .= "<option value=\"$apc\" $selected>$apc</option>";
        }
        $html .= "</select></div>";

        return($html);
    }

    function validator_date($fieldname)
    {
        $v = $this->GetFormValue($fieldname);
        $month = date('m',strtotime($v['m'] . ' 1,2000')) ;
        if( checkdate($month,$v['d'],$v['y']) )
        {
            $str = "{$v['m']} {$v['d']},{$v['y']} {$v['h']}:{$v['i']} {$v['a']}";
            $this->SetFormValue($fieldname,date( 'Y-m-d H:i', strtotime($str) ));
        }
        else
        {
            $this->SetFieldError($fieldname, "Not a valid date" );
        }
        return(true);
    }

    /**
     * Handles generation of HTML field 
     *
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string of HTML that represents the field
     */
    function generator_localdir($varname,$value='',$class='')
    {
        return( $this->generator_textedit( $varname,$value, $class ) );
    }

    function validator_localdir($fieldname)
    {
        if( $this->validator_textedit($fieldname) )
        {
            $dir = $this->GetFormValue($fieldname);
            if( !file_exists($dir) )
            {
                $this->SetFieldError($fieldname, "This directory doesn't exist");
                return(false);
            }
        }

        return(true);
    }

}

/**
 * Alternative view of HTML form controls
 *
 * This class sets up a form with a grid of form elements
 * useful when editing several records at once.
 *
 */
class CCGridForm extends CCForm
{
    var $_grid_rows;
    var $_column_heads;
    var $_is_normalized;

    /**
     * Constructor
     *
     * Setups the template to handle grid of form elements.
     *
     */
    function CCGridForm()
    {
        $this->CCForm();
        $this->_is_normalized = false;
        $this->_grid_rows = array();
        $this->_column_heads = array();
        $this->SetTemplateVar('show_form_grid',   true );
    }

    /**
     * Add a row of controls to the form.
     *
     * @access public
     * @param integer $key Typically the unique key in the a db representing this record
     * @param array   $row An array of field objects 
     * @see CCForm::AddFormFields
     */
    function AddGridRow($key,&$row)
    {
        $this->_grid_rows[ '#' . $key] = $row;
    }

    /**
     * Add a row of column headers. 
     *
     * This should be called once to setup the column headers
     *
     * @access public
     * @param array $heads An array of strings
     */
    function SetColumnHeader(&$heads)
    {
        $this->_column_heads = $heads;
    }

    /**
     * Overrides base class to handle grid rows
     *
     * This should be called once to setup the column headers, see CCForm::GetFormValues()
     *
     * @access public
     * @param array $values Out parameter to receive values. This is suitable for called CCTable::Update() or CCTable::Insert().
     * 
     */
    function GetFormValues( &$values )
    {
        $this->_normalize_fields();

        return( CCForm::GetFormValues($values) );
    }

    /**
     * This method puts a reference of each field (cell) in the grid and into a format the base class can use for methods like Get/SetValues().
     *
     * @access private
     */
    function _normalize_fields()
    {
        if( $this->_is_normalized )
            return;

        $count = count($this->_grid_rows);
        $keys  = array_keys($this->_grid_rows);
        for( $i = 0; $i < $count; $i++ )
        {
            $grid_row =& $this->_grid_rows[$keys[$i]];
            $count2 = count($grid_row);
            for( $n = 0; $n < $count2; $n++ )
            {
                $grid_cell =& $grid_row[$n];
                $this->_form_fields[$grid_cell['element_name']] = &$grid_cell;
            }
        }

        $this->_is_normalized  = true;
    }

    /**
     * Overrides base class to handle grid rows.
     *
     * Generates template arrays and prepares the form for display.
     * 
     * <code>
     *    $page->AddForm( $form->GenerateForm() );
     *</code>
     * 
     * @returns object $varsname Return $this to make it convienent to add to pages
     */
    function GenerateForm()
    {
        $this->_normalize_fields();

        $headers = array();
        foreach( $this->_column_heads as $ch )
            $headers[] = array( 'column_name' => $ch );

        $this->_template_vars['html_form_grid_columns'] = $headers;
        $this->_template_vars['html_form_grid_rows']    = array();
        $this->_template_vars['html_hidden_fields']     = array();

        foreach( $this->_form_fields as $fieldname => $form_fields )
        {
            if( $form_fields['flags'] & CCFF_HIDDEN )
            {
                $this->_template_vars['html_hidden_fields'][] = 
                        array( 'hidden_name' => $fieldname,
                               'hidden_value' => $form_fields['value']);
            }
            else
            {
                // this is not really defined here...
            }
        }

        $keys  = array_keys($this->_grid_rows);
        $i = 0;
        $rows = array();
        foreach( $keys as $key )
        {
            $grid_row =& $this->_grid_rows[$key];
            $count2 = count($grid_row);
            $template_row = array();
            $form_error = '';
            for( $n = 0; $n < $count2; $n++ )
            {
                $grid_cell =& $grid_row[$n];
                $generator  = 'generator_' . $grid_cell['formatter'];
                $value = empty($grid_cell['value']) ? '' : $grid_cell['value'];
                
                $class = empty($grid_cell['class']) ? '' : $grid_cell['class'];

                if( !empty($grid_cell['form_error']) )
                {
                    $form_error .= '   ' . $grid_cell['form_error'];
                    $class .= "\" style='background:pink' ";
                }

                $template_row[] = array( 'form_grid_element' => 
                               $this->$generator( $grid_cell['element_name'], $value, $class ));
            }
 
            $rows[] = array(  'html_form_grid_fields' => $template_row, 
                                     'grid_row' => ++$i,
                                     'form_error' => $form_error,
                                     'num_columns' => $count2
                                  );
        }


        $this->_template_vars['html_form_grid_rows'] =& $rows;

        $id = strtolower(substr(get_class($this),2));
        $this->_template_vars['form_id'] = $id;

        return( $this );
    }

    /**
     * Overrides base class to handle grid rows.
     *
     * Call this method on POST to verify each field in the form. see CCForm::ValidateFields()
     * @returns boolean true if fields validate, false if there is a problem.
     * @access public
     * 
     */
    function ValidateFields()
    {
        $this->_normalize_fields();

        return( CCForm::ValidateFields() );
    }

    /**
     * Does special array-aware parsing of field names.
     * @param string $name name of field, typically in the form 'something[integer][subfieldname]'
     * @returns value of field
     * @access private
     * 
     */
    function _fetch_post_value($name)
    {
        if( strpos($name,'[') === false )
            return( CCForm::_fetch_post_value($name) );

        // typical format of '$name' is: mi[9][fname]
        //
        // We turn that into:
        //
        //  if( array_key_exists( 'fname', $_POST['mi']['9'] ) )
        //     $value = $_POST['mi']['9']['fname']
        //
        $m = preg_replace('/^([^\]]+)\[/',"[$1][",$name);
        preg_match_all('/\[([^\]]+)]/',$m,$a);
        $c = count($a[1]) - 1;
        $key = $a[1][$c];
        $s = str_replace("[$key]",'',$m);
        $m = "if( array_key_exists( '$key', \$_POST$s) ) \$value = \$_POST$m;";
        $m = preg_replace('/\[([^\]]+)\]/',"['$1']",$m);

        $value = null;
        eval($m);
        return($value);
    }

}


/**
 * Sets up an HTML form that is capable of receiving file uploads.
 *
 * Derive from this class when your form has file upload fields. It has 
 * built in support avatar images.
 *
 * @author Victor
 */
class CCUploadForm extends CCForm
{
    /**
     * Constructor
     *
     * Setups the template to handle file uploads.
     *
     */
    function CCUploadForm()
    {
        $this->CCForm();

       $this->_template_vars['form-data'] = 'multipart/form-data';
    }

    /**
     * Handles generation of HTML field for avatars.
     *
     * Generates HTML for displaying browse field and (if the image exists)
     * a thumbnail of the image and a 'delete this' check box. It requires
     * that the field information array contains an entry called 'upload_dir'
     * is the local directory (relative, <b>not</b> full path) that is to
     * receive the image upload. This method is called automatically from CCForm::GenerateForm()
     *
     * @param string $varname Name of the HTML field
     * @param string $value   Image file name (optional)
     * @param string $class   CSS class (ignored)
     * @returns string of HTML that represents the field
     */
    function generator_avatar($varname,$value='',$class='')
    {
        $html = $this->generator_upload($varname,$value,$class);

        if( !empty($value) )
        {
            $imagedir = $this->GetFormFieldItem($varname,'upload_dir');
            $path     = $imagedir . '/' . $value;
            $html .= '<br /><img src="' . $path . '" /><br /> '.
              '<input type="checkbox" id="' . $varname . '_delete" name="' . $varname . '_delete" />'.
              '<input type="hidden"   id="' . $varname . '_file"   name="' . $varname . '_file" ' .
                  'value="' . $value . '" />'.
                     ' Delete this image';
        }

        return($html);
    }

    /**
     * Validates HTML field for avatars at POST time.
     *
     * Checks for such things as 'required' flags. Also checks against 
     * an ield information array about maximum height/width requirements. Generates HTML for displaying browse field and (if the image exists).
     * This method is called automatically from CCForm::ValidateFields()
     *
     * @access public
     * @param string $fieldname Name of the HTML field
     * @returns boolean $bool true if field data passes validation, false on errors
     */
    function validator_avatar($fieldname)
    {
        $retval = CCUploadForm::validator_upload($fieldname);

        if( $retval )
        {
            $filesobj = $this->GetFormValue($fieldname);
            if( !$filesobj || ($filesobj['error'] == UPLOAD_ERR_NO_FILE) )
                return(true);

            $maxheight = intval($this->GetFormFieldItem($fieldname,'maxheight'));
            $maxwidth  = intval($this->GetFormFieldItem($fieldname,'maxwidth'));
            if( $maxheight && $maxwidth )
            {
                $tmp_name   = $filesobj['tmp_name'];
                list( $width, $height ) = getimagesize($tmp_name);
                if( !$width || !$height )
                {
                    $this->SetFieldError($fieldname,"Image size could not be determined");
                    $retval = false;
                }
                else if( ($width > $maxwidth) || ($height > $maxheight ) )
                {
                    $this->SetFieldError($fieldname,"Image must be no larger than 93px x 93px");
                    $retval = false;
                }
            }
        }

        return( $retval );
    }

    /**
     * Handles generation of HTML field for simple file uploads.
     *
     * Generates HTML for displaying browse field. This method is called automatically from CCForm::GenerateForm()
     *
     * @param string $varname Name of the HTML field
     * @param string $value   (ignored)
     * @param string $class   CSS class (optional)
     * @returns string of HTML that represents the field
     */
    function generator_upload($varname,$value='',$class='')
    {
        return( "<input type=\"file\" id=\"$varname\" name=\"$varname\" class=\"$class\" />" );
    }

    /**
     * Validates HTML field for file uploads at POST time.
     *
     * Checks for such things as 'required' flags. Also checks against 
     * PHP system errors on upload. If successful will populate the 
     * the 'value' field of the fields info array with the name of the
     * target file and creates an entry called 'fileobj' that contains 
     * a copy of the PHP $_FILES object for this field.
     * This method is called automatically from CCForm::ValidateFields()
     *
     * @param string $fieldname Name of the HTML field
     * @returns boolean $bool true if field data passes validation, false on errors
     */
    function validator_upload($fieldname)
    {
        $filesobj = $_FILES[$fieldname];
        $flags = $this->GetFormFieldItem($fieldname,'flags');

        if( !($flags & CCFF_REQUIRED) && ($filesobj['error'] == UPLOAD_ERR_NO_FILE) )
            return(true);

        if( $filesobj['error'] != 0 )
        {
            $problems = array( UPLOAD_ERR_INI_SIZE  => 'File too big',
                               UPLOAD_ERR_FORM_SIZE => 'File too big',
                               UPLOAD_ERR_PARTIAL   => 'File was not fully uloaded',
                               UPLOAD_ERR_NO_FILE   => 'Missing file name');

            $this->SetFieldError($fieldname, $problems[$filesobj['error']]);
            return(false);
        }

        $filesobj['name'] = CCUtil::StripSlash($filesobj['name']);
        $this->SetFormValue($fieldname,$filesobj);

        return(true);
    }


    /**
     * Avatar upload is not completed until this helper is called.
     *
     * This method should be called (after field verification) to
     * move the uploaded to the right location. It requires
     * that the field information array contains an entry called 'upload_dir'
     * is the local directory (relative, <b>not</b> full path) that is to
     * receive the image upload. 
     *
     * @param string $fieldname Name of the HTML field
     * @param string $imagedir Directory to put the uploaded image into
     */
    function FinalizeAvatarUpload($fieldname,$imagedir)
    {
        $ok = true;
        $delfield = $fieldname . '_delete';
        if( array_key_exists($delfield,$_POST) ) // && ($_POST[$delfield] == 'on') )
        {
            $oldname  = CCUtil::StripText($_POST[$fieldname . '_file']);
            if( $oldname )
            {
                CCUtil::MakeSubdirs( $imagedir ); 
                $path = realpath($imagedir) . '/' . $oldname; 
                unlink( $path );

                // we have to strip the SKIP flag to maek sure the blank
                // record gets written to the db
                $flags = $this->GetFormFieldItem($fieldname,'flags');
                $this->SetFormFieldItem($fieldname,'flags', $flags &= ~CCFF_SKIPIFNULL);
            }
            $this->SetFormValue($fieldname,'');
        }
        else
        {
            $filesobj = $this->GetFormValue($fieldname);

            if( $filesobj )
            {
                CCUtil::MakeSubdirs($imagedir);

                $realpath = realpath( $imagedir) . '/' . $filesobj['name'];

                if( file_exists($realpath) )
                    unlink($realpath);

                $ok = move_uploaded_file($filesobj['tmp_name'],$realpath );

                if( !$ok )
                    $filesobj['name'] = null;

                $this->SetFormValue($fieldname,$filesobj['name']);
            }
        }
        
        return( $ok );
    }


}

?>