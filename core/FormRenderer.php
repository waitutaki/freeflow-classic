<?php
namespace Core;

use Core\Models\FormsModel;

class FormRenderer
{
    public static function renderForm(int $formId): string
    {
        $form = FormsModel::find($formId);
        if (!$form) {
            return '<p>Form not found.</p>';
        }

        $formXml = $form['form_xml'];
        $dom = new \DOMDocument();
        $dom->loadXML($formXml);
        $fields = $dom->getElementsByTagName('field');

        $html = '<form method="post" action="/forms/submit">';
        $html .= '<input type="hidden" name="form_id" value="' . $formId . '">';
        $html .= Csrf::tokenField();

        foreach ($fields as $field) {
            $type = $field->getAttribute('type');
            $name = $field->getAttribute('name');
            $label = $field->getAttribute('label');
            $required = $field->getAttribute('required') === 'true';

            $html .= '<div class="mb-3">';
            $html .= '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';

            switch ($type) {
                case 'text':
                    $html .= '<input type="text" class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"' . ($required ? ' required' : '') . '>';
                    break;
                case 'email':
                    $html .= '<input type="email" class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"' . ($required ? ' required' : '') . '>';
                    break;
                case 'textarea':
                    $html .= '<textarea class="form-control" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"' . ($required ? ' required' : '') . '></textarea>';
                    break;
                case 'select':
                    $html .= '<select class="form-select" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '"' . ($required ? ' required' : '') . '>';
                    $options = $field->getElementsByTagName('option');
                    foreach ($options as $option) {
                        $value = $option->getAttribute('value');
                        $text = $option->textContent;
                        $html .= '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($text) . '</option>';
                    }
                    $html .= '</select>';
                    break;
                // Add more field types as needed
            }

            $html .= '</div>';
        }

        $html .= '<button type="submit" class="btn btn-primary">Submit</button>';
        $html .= '</form>';

        return $html;
    }
}