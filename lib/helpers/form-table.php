<?php
/**
 * Handles standard form creation
 *
 * @package Grey Suit Retail
 * @since 1.0
 */
class FormTable {
    /**
     * Hold variables
     */
    protected $name;
    protected $action;
    protected $method;
    protected $fields;
    protected $errs = '';
    protected $submit;
    protected $submit_classes = 'button';
    protected $attributes = array();
    protected $submit_column = 2;

    /**
     * Hold Validator
     * @var Validator
     */
    protected $v;

    /**
     * Hold Resources object
     * @var Resources
     */
    protected $resources;

    /**
     * Constructor -- Create the name
     *
     * @param string $name
     * @param string $action [optional]
     * @param string $method [optional]
     */
    public function __construct( $name, $action = '', $method = 'post' ) {
        $this->name = $name;
        $this->action = $action;
        $this->method = $method;

        // Need to set this
        $this->submit = _('Save');
    }

    /**
     * Set the action dynamically
     *
     * @param string $action
     */
    public function set_action( $action ) {
        $this->action = $action;
    }

    /**
     * Add a field
     *
     * @param string $type
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     * @return FormTable_Field|FormTable_Select
     */
    public function add_field( $type, $nice_name, $name = '', $value = '' ) {
        $class = 'FormTable_' . ucwords( $type );
        $field = new $class( $nice_name, $name, $value );

        // Tie them together
        $this->fields[] = $field;

        return $field;
    }

    /**
     * Submit Button
     *
     * @param string $text
     * @param string $classes [optional] Space separate values
     * @param int $column [optional]
     * @return FormTable
     */
    public function submit( $text, $classes = '', $column = 2 ) {
        $this->submit = $text;

        if ( !empty( $classes ) )
            $this->submit_classes .= " $classes";

        $this->submit_column = $column;

        return $this;
    }

    /**
     * Sets an attribute
     *
     * @param string $attribute
     * @param string $value
     * @return FormTable
     */
    public function attribute( $attribute, $value ) {
        $this->attributes[] = $attribute . '="' . $value . '"';
        return $this;
    }

    /**
     * Error
     *
     * @param string $error
     */
    public function error( $error ) {
        $this->errs .= $error . "<br />";
    }

    /**
     * Generate Form
     *
     * @return string
     */
    public function generate_form() {
        $this->_validator();
        $attributes = ( 0 == count( $this->attributes ) ) ? '' : ' ' . implode( ' ', $this->attributes );
		$html = $hidden = '';

        if ( !is_null( $this->errs ) && !empty( $this->errs ) )
            $html .= '<p class="red">' . $this->errs . '</p><br />';

        $html .= '<form name="' . $this->name . '" id="' . $this->name . '" action="' . $this->action . '" method="' . $this->method . '"' . $attributes . '>';
        $html .= '<table class="width-auto">';

        // Count
        $i = 0;

        foreach ( $this->fields as $f ) {
            // The count will make sure radio buttons have unique IDs
            $generated_html = $f->generate_html( $i );
            if ( 'hidden' == $f->get_type() ) {
                $hidden .= $generated_html;
            } else {
                $html .= $generated_html;
            }
            $i++;
        }

        $html .= '<tr><td colspan="2">&nbsp;</td></tr>';
        if ( 2 == $this->submit_column ) {
            $html .= '<tr><td>&nbsp;</td><td><input type="submit" class="' . $this->submit_classes . '" value="' . $this->submit . '" /></td></tr>';
        } else {
            $html .= '<tr><td><input type="submit" class="' . $this->submit_classes . '" value="' . $this->submit . '" /></td></tr>';
        }

        $html .= '</table>';
        $html .= nonce::field( 'form-' . $this->name, '_nonce', false );
        $html .= $hidden;
        $html .= '</form>';

        $html .= $this->v->js_validation();

        return $html;
    }

    /**
     * Determine if it was posted
     *
     * @return bool
     */
    public function posted() {
        if ( isset( $_POST['_nonce'] ) && nonce::verify( $_POST['_nonce'], 'form-' . $this->name ) ) {
            $this->_validator();
            $this->errs = $this->v->validate();
            return empty( $this->errs );
        }

        return false;
    }

    /**
     * Instantiate the validator class
     */
    private function _validator() {
        if ( is_null( $this->v ) ) {
            $this->v = new Validator( $this->name );

            foreach ( $this->fields as $f ) {
               $f->validation( $this->v );
            }
       }
    }
}

abstract class FormTable_Field {
    /**
     * Hold variables
     */
    protected $type;
    protected $nice_name;
    protected $name;
    protected $value;
    protected $validation = NULL;
    protected $attributes = array();
    protected $required = false;

    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        if ( is_array( $value ) ) {
            switch( strtoupper( $value[0] ) ) {
                case 'GET':
                    $value = isset( $_GET[$value[1]] ) ? $_GET[$value[1]] : '';
                break;

                case 'POST':
                    $value = isset( $_POST[$value[1]] ) ? $_POST[$value[1]] : '';
                break;

                default:break;
            }
        }

        $this->nice_name = $nice_name;
        $this->name = ( empty( $name ) ) ? format::slug( $nice_name ) : $name;
        $this->value = ( isset( $_POST[$name] ) ) ? $_POST[$name] : $value;
    }

    /***** METHOD CHAINING *****/

    /**
     * Add Validation
     *
     * @param string $type
     * @param string $message
     * @return FormTable_Field
     */
    public function add_validation( $type, $message ) {
        switch ( $type ) {
            case 'req':
            case 'required':
                $this->required = true;
            break;
        }

        $this->validation[$type] = $message;

        return $this;
    }

    /**
     * Sets an attribute
     *
     * @param string $attribute
     * @param string $value
     * @return FormTable_Field
     */
    public function attribute( $attribute, $value ) {
        $this->attributes[] = $attribute . '="' . $value . '"';

        return $this;
    }

    /***** OTHER METHODS *****/

    /**
     * Get Type
     *
     * @return string
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Set Value
     *
     * @param string $value
     */
    public function set_value( $value ) {
        $this->value = $value;
    }

    /**
     * Validation
     *
     * Adds validation to the validator
     *
     * @param Validator $v
     */
    public function validation( $v ) {
        if ( !is_null( $this->validation ) )
        foreach ( $this->validation as $type => $message ) {
            $v->add_validation( $this->name, $type, $message );
        }
    }

    /***** PROTECTED METHODS *****/

    /**
     * Format attributes
     *
     * @return string
     */
    protected function format_attributes() {
        return ( 0 == count( $this->attributes ) ) ? '' : ' ' . implode( ' ', $this->attributes );
    }

    /**
     * Get the ID based off the name
     *
     * @param int|string $count [optional]
     * @return string
     */
    protected function id( $count = '' ) {
        return preg_replace( '/[^a-zA-Z0-9_-]/', '', $this->name . $count );
    }

    /***** ABSTRACT METHODS *****/
    abstract public function generate_html( $count = 0 );
}

/**
 * Text Field
 */
class FormTable_Text extends FormTable_Field {
    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        parent::__construct( $nice_name, $name, $value );

        $this->type = 'text';
    }

    /**
     * Generate just the HTML
     *
     * @return string
     */
    public function generate() {
        return '<input type="text" class="tb" name="' . $this->name . '" id="' . $this->id() . '" value="' . $this->value . '"' . $this->format_attributes() .' />';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        $html = '<tr><td>';
        $html .= '<label for="' . $this->id() . '">' . $this->nice_name . ':';

        if ( $this->required )
            $html .= ' <span class="red">*</span>';

        $html .= '</label>';
        $html .= '</td><td>';
        $html .= $this->generate();
        $html .= '</td></tr>';

        return $html;
    }
}
/**
 * Textarea Field
 */
class FormTable_Textarea extends FormTable_Field {
    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        parent::__construct( $nice_name, $name, $value );

        $this->type = 'textarea';
    }

    /**
     * Generate just the HTML
     *
     * @return string
     */
    public function generate() {
        return '<textarea name="' . $this->name . '" id="' . $this->id() . '" cols="50" rows="3" ' . $this->format_attributes() .'>' . $this->value . '</textarea>';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        if ( empty( $this->nice_name ) ) {
            $html = '<tr><td colspan="2">';
            $html .= $this->generate();
            $html .= '</td></tr>';
        } else {
            $html = '<tr><td class="top">';
            $html .= '<label for="' . $this->id() . '">' . $this->nice_name . ':';

            if ( $this->required )
                $html .= ' <span class="red">*</span>';

            $html .= '</label>';
            $html .= '</td><td class="top">';
            $html .= $this->generate();
            $html .= '</td></tr>';
        }

        return $html;
    }
}

/**
 * Password Field
 */
class FormTable_Password extends FormTable_Field {
    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        parent::__construct( $nice_name, $name, $value );

        $this->type = 'password';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        $html = '<tr><td>';
        $html .= '<label for="' . $this->id() . '">' . $this->nice_name . ':';

        if ( $this->required )
            $html .= ' <span class="red">*</span>';

        $html .= '</label>';
        $html .= '</td><td>';
        $html .= '<input type="password" class="tb" name="' . current( explode( '|', $this->name ) ) . '" id="' . $this->id() . '" value="' . $this->value . '"' . $this->format_attributes() .' />';
        $html .= '</td></tr>';

        return $html;
    }
}

/**
 * Select Field
 */
class FormTable_Select extends FormTable_Field {
    /**
     * Hold the options for the select
     * @var array
     */
    protected $options = array();

    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        parent::__construct( $nice_name, $name, $value );

        $this->type = 'select';

        return $this;
    }

    /**
     * Sets the options if its a select
     *
     * @param array $options
     * @return FormTable_Select
     */
    public function options( array $options ) {
        $this->options = $options;

        return $this;
    }

    /**
     * Generate Select
     *
     * @return string
     */
    public function generate() {
        $html = '<select name="' . $this->name . '" id="' . preg_replace( '/[\[\]]/', '', $this->id() ) . '"' . $this->format_attributes() . '>';

        foreach ( $this->options as $option_value => $option_name ) {
            if ( is_array( $this->value ) ) {
                $selected = ( in_array( $option_value, $this->value ) ) ? ' selected="selected"' : '';
            } else {
                $selected = ( $this->value == $option_value ) ? ' selected="selected"' : '';
            }
            $html .= '<option value="' . $option_value . '"' . $selected . '>' . $option_name . '</option>';
        }

        return $html;
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        if ( empty( $this->nice_name ) ) {
            $html = '<tr><td colspan="2">';
            $html .= $this->generate();
            $html .= '</td></tr>';
        } else {
            $html = '<tr><td class="top">';
            $html .= '<label for="' . $this->id() . '">' . $this->nice_name . ':';

            if ( $this->required )
                $html .= ' <span class="red">*</span>';

            $html .= '</label>';
            $html .= '</td><td>';
            $html .= $this->generate();

            $html .= '</td></tr>';
        }

        return $html;
    }
}

/**
 * Radio button
 */
class FormTable_Radio extends FormTable_Field {
    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        parent::__construct( $nice_name, $name, $value );

        $this->type = 'radio';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        $html = '<tr><td>&nbsp;</td><td>';
        $html .= '<input type="radio" class="rb" name="' . $this->name . '" id="' . $this->id( $count ) . '" value="' . $this->value . '"';

        if ( $_POST[$this->name] == $this->value )
            $html .= ' checked="checked"';

        $html .= $this->format_attributes() . ' /> <label for="' . $this->id( $count ) . '">' . $this->nice_name;

        if ( $this->required )
            $html .= ' <span class="red">*</span>';

        $html .= '</label></td></tr>';

        return $html;
    }
}


/**
 * Checkbox field
 */
class FormTable_Checkbox extends FormTable_Field {
    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $nice_name
     * @param string $name [optional]
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $nice_name, $name = '', $value = '' ) {
        parent::__construct( $nice_name, $name, $value );

        $this->type = 'checkbox';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        $html = '<tr><td>&nbsp;</td><td>';

        $html .= '<input type="checkbox" class="cb" name="' . $this->name . '" id="' . $this->id( $count ) . '" value="1"';

        if ( '1' == $this->value )
            $html .= ' checked="checked"';

        $html .= $this->format_attributes() . ' /> <label for="' . $this->id( $count ) . '">' . $this->nice_name;

        if ( $this->required )
            $html .= ' <span class="red">*</span>';

        $html .= '</label></td></tr>';

        return $html;
    }
}

/**
 * Hidden field
 */
class FormTable_Hidden extends FormTable_Field {
    /**
     * Constructor -- Create a field with validation options
     *
     * @param string $name
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $name, $value = '' ) {
        parent::__construct( '', $name, $value );

        $this->type = 'hidden';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        return '<input type="hidden" name="' . $this->name . '" id="' . $this->id() . '" value="' . $this->value . '"' . $this->format_attributes() .' />';
    }
}

/**
 * Title Row
 */
class FormTable_Title extends FormTable_Field {
    /**
     * Constructor -- Create a title row
     *
     * @param string $name
     */
    public function __construct( $name ) {
        parent::__construct( $name );

        $this->type = 'title';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        return '<tr><td><strong class="big">' . $this->nice_name . '</strong></td><td>&nbsp;</td></tr>';
    }
}

/**
 * Blank Row
 */
class FormTable_Blank extends FormTable_Field {
    /**
     * Constructor -- Create a title row
     *
     */
    public function __construct() {
        parent::__construct( '' );

        $this->type = 'blank';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        return '<tr><td colspan="2">&nbsp;</td></tr>';
    }
}

/**
 * Blank Row
 */
class FormTable_Row extends FormTable_Field {
    /**
     * Constructor -- Create a row
     *
     * @param string $name
     * @param string $value [optional] the preset value of the field (other than the post)
     */
    public function __construct( $name, $value = '' ) {
        parent::__construct( '', $name, $value );

        $this->type = 'row';
    }

    /**
     * Generate HTML
     *
     * @param int $count [optional]
     * @return string
     */
    public function generate_html( $count = 0 ) {
        return '<tr><td><strong>' . $this->name . '</strong></td><td><strong>' . $this->value . '</strong></td></tr>';
    }
}