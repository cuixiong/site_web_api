<?php
namespace App\Http\Controllers\Pay;


class PayForm
{
    const AS_ARRAY = 1;
    const AS_LINK = 2;
    const AS_AUTO = 3;

    /** @var string */
    public $actionUrl;
    /** @var array */
    public $formData;
    public $formId = 'pay_form';
    public $target = '_self';

    /**
     * @param string $actionUrl
     * @param string $formData
     */
    public function __construct($actionUrl, $formData)
    {
        $this->actionUrl = $actionUrl;
        $this->formData = $formData;
    }

    /**
     * @param string $actionUrl
     * @param array $formData
     * @return string
     */
    public function asAutoPost()
    {
        $form = '<form id="'.$this->formId.'" action="'.$this->actionUrl.'" method="post" target="'.$this->target.'" style="display:none;">';
        foreach ($this->formData as $key => $value) {
            $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
        }
        $form .= '<input type="submit" value="Submit"/>';
        $form .= '</form>';
        $form .= '<script>document.getElementById("'.$this->formId.'").submit();</script>';

        return $form;
    }

    /**
     * @param string $actionUrl
     * @param array $formData
     * @param string $link
     * @return array
     */
    public function asLink($link = 'payment')
    {
        $form = '<form id="'.$this->formId.'" action="'.$this->actionUrl.'" method="post" target="'.$this->target.'">';
        foreach ($this->formData as $key => $value) {
            $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
        }
        $form .= '<input style="color:rgb(24, 102, 154);background-color:transparent;border: 0px none;font-size:15px;text-decoration:underline;cursor:pointer;" type="submit" value="'.$link.'"/>';
        $form .= '</form>';

        return $form;
    }

    /**
     * @param string $actionUrl
     * @param array $formData
     * @return array
     */
    public function asArray()
    {
        return [
            'action_url' => $this->actionUrl,
            'form_data' => $this->formData
        ];
    }
}
