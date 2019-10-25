<?php

namespace Febalist\LaravelForm;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use MarvinLabs\Html\Bootstrap\Bootstrap;
use MarvinLabs\Html\Bootstrap\Elements\ControlWrapper;
use Spatie\Html\BaseElement;
use Spatie\Html\Elements\Div;

class Form
{
    protected $bootstrap;
    protected $model;

    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function open($options = [])
    {
        if ($url = $options['action'] ?? $options['url'] ?? null) {
            $action = url($url);
        } elseif ($route = $options['route'] ?? null) {
            if (!is_array($route)) {
                $route = [$route];
            }
            $action = route(...$route);
        } else {
            $action = url()->current();
        }

        $this->model = $options['model'] ?? null;

        $method = $options['method'] ?? 'POST';

        $options['files'] = $options['files'] ?? true;

        return $this->bootstrap->openForm($method, $action, $options);
    }

    /**
     * ($model, $store, $update) → route($store), route($update, $model)
     * ($model, $prefix) → route("$prefix.store"), route("$prefix.update", $model)
     * ($model, [$prefix, $params]) → route("$prefix.store", $params), route("$prefix.update", [$params, $model])
     * ($model) → route('models.store'), route('models.update', $model)
     */
    public function openModel(Model $model, $store = null, $update = null)
    {
        $this->model = $model;

        $method = $model->exists ? 'PUT' : 'POST';

        if ($update && $store) {
            $params = $model->exists ? $update : $store;
        } else {
            $params = [];
            if (is_array($store)) {
                $prefix = array_shift($store);
                $params = $store;
            } elseif ($store) {
                $prefix = $store;
            } else {
                $prefix = str_plural(snake_case(class_basename($model)));
            }
            $params = $model->exists ? ["$prefix.update", $params] : ["$prefix.store", $params];
        }

        $params = is_array($params) ? $params : [$params];
        $params[1] = array_wrap($params[1] ?? []);
        if ($model->exists) {
            $params[1][] = $model->getRouteKey();
        }

        $action = str_contains($params[0], '@') ? action(...$params) : route(...$params);

        return $this->bootstrap->openForm($method, $action, [
            'model' => $model,
            'files' => true,
        ]);
    }

    public function close($submit = null)
    {
        $this->model = null;

        $submit = $submit ? $this->submit($submit) : '';
        $close = $this->bootstrap->closeForm();

        return $this->html($submit, $close);
    }

    public function submit($label, $attributes = [])
    {
        $element = $this->bootstrap->submit($label);

        return $this->group($element, $attributes);
    }

    public function hidden($name, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $element = $this->bootstrap->hidden($name, $value)->attributes($attributes);

        return $this->bootstrap->formGroup($element)->class('text-center mb-0');
    }

    public function text($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $element = $this->bootstrap->text($name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function textarea($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $element = $this->bootstrap->textArea($name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function email($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $element = $this->bootstrap->email($name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function password($name, $label = null, $attributes = [])
    {
        $name = $this->name($name);

        $element = $this->bootstrap->password($name)->value('');

        return $this->group($element, $attributes, $label);
    }

    public function number($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $element = $this->bootstrap->input('number', $name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function integer($name, $label = null, $value = null, $attributes = [])
    {
        $attributes['step'] = $attributes['step'] ?? 1;

        return $this->number($name, $label, $value, $attributes);
    }

    public function float($name, $label = null, $value = null, $attributes = [])
    {
        $attributes['step'] = $attributes['step'] ?? 0.01;

        return $this->number($name, $label, $value, $attributes);
    }

    public function date($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value, function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $name = $this->name($name);

        $element = $this->bootstrap->input('date', $name, $value);

        $attributes['data-date-format'] = 'YYYY-MMMM-DD';

        return $this->group($element, $attributes, $label);
    }

    public function time($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value, function ($value) {
            return Carbon::parse($value)->format('H:i:s');
        });
        $name = $this->name($name);

        $element = $this->bootstrap->input('time', $name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function select($name, $label, $options, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $options = array_value($options);

        $empty = $this->pull_attribute($attributes, 'empty');
        if ($empty !== null) {
            $options = ['' => $empty === true ? '' : $empty] + $options;
        }

        $element = $this->bootstrap->select($name, $options, $value);

        return $this->group($element, $attributes, $label);
    }

    public function select_combine($name, $label, $options, $value = null, $attributes = [])
    {
        $options = array_value($options);
        $options = array_combine_values($options);

        return $this->select($name, $label, $options, $value, $attributes);
    }

    public function checkbox($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $default = Div::create()->html("<input type=\"hidden\" name=\"$name\" value=\"0\">");
        $element = $this->bootstrap->checkBox($name, $label, $value)->value(1);
        $group = $this->group($element, $attributes);

        return $this->html($default, $group);
    }

    public function checkboxes($name, $label, $options, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $div = Div::create()->addChild('<div class="mt-2"></div>');
        foreach ($options as $key => $title) {
            $default = "<input type=\"hidden\" name=\"{$name}[$key]\" value=\"0\">";
            $checkbox = $this->bootstrap->checkBox("{$name}[$key]", $title, $value[$key] ?? false)->value(1);
            $div = $div->addChildren($default)->addChildren($checkbox);
        }

        return $this->group($div, $attributes, $label);
    }

    public function checkboxes_combine($name, $label, $options, $value = null, $attributes = [])
    {
        $options = array_value($options);
        $options = array_combine_values($options);

        return $this->checkboxes($name, $label, $options, $value, $attributes);
    }

    public function radio($name, $label, $options, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value);
        $name = $this->name($name);

        $options = array_value($options);

        $element = $this->bootstrap->radioGroup($name, $options, $value)->class('mt-2');

        return $this->group($element, $attributes, $label);
    }

    public function radio_combine($name, $label, $options, $value = null, $attributes = [])
    {
        $options = array_value($options);
        $options = array_combine_values($options);

        return $this->radio($name, $label, $options, $value, $attributes);
    }

    public function file($name, $label = null, $attributes = [])
    {
        $name = $this->name($name);

        if (config('form.file.custom')) {
            $element = $this->bootstrap->file($name);
        } else {
            $element = $this->bootstrap->simpleFile($name);
        }

        return $this->group($element, $attributes, $label);
    }

    public function files($name, $label = null, $attributes = [])
    {
        $attributes[] = 'multiple';
        $name = str_finish($name, '[]');

        return $this->file($name, $label, $attributes);
    }

    public function image($name, $label = null, $attributes = [])
    {
        $attributes['accept'] = $attributes['accept'] ?? 'image/*';

        return $this->file($name, $label, $attributes);
    }

    public function images($name, $label = null, $attributes = [])
    {
        $attributes['accept'] = $attributes['accept'] ?? 'image/*';

        return $this->files($name, $label, $attributes);
    }

    public function plain($label = null, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->text('', $value)->readOnly(true);

        return $this->group($element, $attributes, $label);
    }

    public function datalist($id, $options)
    {
        $html = "<datalist id=\"$id\">";
        foreach ($options as $option) {
            $option = e($option);
            $html .= "<option value=\"$option\">";
        }
        $html .= '</datalist>';

        return Div::create()->html($html);
    }

    protected function group(BaseElement $element, $attributes = [], $label = null)
    {
        $help = array_pull($attributes, 'help');
        $prefix = array_pull($attributes, 'prefix');
        $suffix = array_pull($attributes, 'suffix');
        $datalist = array_pull($attributes, 'datalist');

        if ($element instanceof ControlWrapper) {
            $element = $element->controlAttributes($attributes);
        } else {
            $element = $element->attributes($attributes);
        }

        if ($prefix || $suffix) {
            $element = $this->bootstrap->inputGroup($element, $prefix, $suffix);
        }

        if ($datalist) {
            $id = $element->getAttribute('id').'_datalist';
            $element = Div::create()->html([
                $element->attribute('list', $id),
                $this->datalist($id, $datalist),
            ]);
        }

        return $this->bootstrap->formGroup($element, $label ?: '', $help)->showAsRow();
    }

    protected function html(...$elements)
    {
        $html = '';

        foreach ($elements as $element) {
            $html .= (string) $element;
        }

        return new HtmlString($html);
    }

    protected function value($name, $value = null, callable $transform = null)
    {
        $value = old($name) ?? $value ?? data_get($this->model, $name);

        if (isset($value) && $transform) {
            $value = $transform($value);
        }

        return $value;
    }

    protected function pull_attribute(&$attributes, $attribute)
    {
        $value = array_pull($attributes, $attribute);

        if (in_array($attribute, $attributes)) {
            $attributes = array_without($attributes, $attribute);
            $value = true;
        }

        return $value;
    }

    protected function name($name)
    {
        $array = explode('.', $name);

        $name = array_shift($array);

        foreach ($array as $item) {
            $name .= "[$item]";
        }

        return $name;
    }
}
