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

        $method = $options['method'] ?? 'POST';

        $options['files'] = $options['files'] ?? true;

        return $this->bootstrap->openForm($method, $action, $options);
    }

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
        $params[1] = is_array($params[1] ?? []) ? $params[1] : [$params[1]];
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

    public function hidden($name, $value = null)
    {
        return $this->bootstrap->hidden($name, $value);
    }

    public function text($name, $label = null, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->text($name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function textarea($name, $label = null, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->textArea($name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function email($name, $label = null, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->email($name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function password($name, $label = null, $attributes = [])
    {
        $element = $this->bootstrap->password($name)->value('');

        return $this->group($element, $attributes, $label);
    }

    public function number($name, $label = null, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->input('number', $name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function integer($name, $label = null, $value = null, $attributes = [])
    {
        $attributes['step'] = 1;

        return $this->number($name, $label, $value, $attributes);
    }

    public function float($name, $label = null, $value = null, $attributes = [])
    {
        $attributes['step'] = 0.01;

        return $this->number($name, $label, $value, $attributes);
    }

    public function date($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value, function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $element = $this->bootstrap->input('date', $name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function time($name, $label = null, $value = null, $attributes = [])
    {
        $value = $this->value($name, $value, function ($value) {
            return Carbon::parse($value)->format('H:i:s');
        });
        $element = $this->bootstrap->input('time', $name, $value);

        return $this->group($element, $attributes, $label);
    }

    public function select($name, $label = null, $options, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->select($name, $options, $value);

        return $this->group($element, $attributes, $label);
    }

    public function checkbox($name, $label = null, $value = null, $attributes = [])
    {
        $default = Div::create()->html("<input type=\"hidden\" name=\"$name\" value=\"0\">");
        $element = $this->bootstrap->checkBox($name, $label, $value)->value(1);
        $group = $this->group($element, $attributes);

        return $this->html($default, $group);
    }

    public function radio($name, $label = null, $options, $value = null, $attributes = [])
    {
        $element = $this->bootstrap->radioGroup($name, $options, $value);

        return $this->group($element, $attributes, $label);
    }

    public function file($name, $label = null, $attributes = [])
    {
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

        return $this->file($name, $label, $attributes);
    }

    public function image($name, $label = null, $attributes = [])
    {
        $attributes['accept'] = 'image/*';

        return $this->file($name, $label, $attributes);
    }

    public function images($name, $label = null, $attributes = [])
    {
        $attributes['accept'] = 'image/*';

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
        $value = $value ?? $this->model->$name ?? null;

        if (isset($value) && $transform) {
            $value = $transform($value);
        }

        return $value;
    }
}
