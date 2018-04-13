<?php

namespace Febalist\LaravelForm;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use MarvinLabs\Html\Bootstrap\Bootstrap;
use Spatie\Html\BaseElement;

/**
 * @mixin Bootstrap
 * @method BaseElement textarea($name, $label = '', $value = null, $attributes = [])
 * @method BaseElement text($name, $label = '', $value = null, $attributes = [])
 * @method BaseElement number($name, $label = '', $value = null, $attributes = [])
 * @method BaseElement date($name, $label = '', $value = null, $attributes = [])
 * @method BaseElement time($name, $label = '', $value = null, $attributes = [])
 */
class Form
{
    protected $bs;
    protected $model;

    public function __construct(Bootstrap $bs)
    {
        $this->bs = $bs;
    }

    public function __call($type, $arguments)
    {
        $arguments[2] = $this->value($type, $arguments[0], $arguments[2] ?? null);

        $types = ['text', 'number', 'date', 'time', 'textarea', 'file'];
        if (in_array($type, $types)) {
            return $this->elementGroup($type, ...$arguments);
        }

        return $this->bs->$type(...$arguments);
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

        return $this->bs->openForm($method, $action, $options);
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

        return $this->bs->openForm($method, $action, [
            'model' => $model,
            'files' => true,
        ]);
    }

    public function close($submit = null)
    {
        $this->model = null;

        if ($submit) {
            $submit = bs()->formGroup(bs()->submit($submit), '')->showAsRow()->toHtml();
        }
        $html = $submit.$this->bs->closeForm()->toHtml();

        return new HtmlString($html);
    }

    public function select($name, $label = '', $options, $value = null, $attributes = [])
    {
        $value = $this->value('select', $name, $value);
        $element = $this->bs->select($name, $options, $value)->attributes($attributes);

        return $this->group($element, $label);
    }

    public function file($name, $label = '', $attributes = [])
    {
        $element = $this->bs->file($name)->attributes($attributes);

        return $this->group($element, $label);
    }

    public function files($name, $label = '', $attributes = [])
    {
        $attributes['multiple'] = true;

        return $this->files($name, $label, $attributes);
    }

    public function image($name, $label = '', $attributes = [])
    {
        $attributes['accept'] = 'image/*';

        return $this->file($name, $label, $attributes);
    }

    public function images($name, $label = '', $attributes = [])
    {
        $attributes['accept'] = 'image/*';

        return $this->files($name, $label, $attributes);
    }

    public function password($name, $label = '', $attributes = [])
    {
        $element = $this->bs->password($name)->attributes($attributes);

        return $this->group($element, $label);
    }

    protected function dateValue($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }

        return $value;
    }

    protected function timeValue($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('H:i:s');
        }

        return $value;
    }

    /** @return BaseElement */
    protected function element($type, ...$arguments)
    {
        if (method_exists($this->bs, $type)) {
            return $this->bs->$type(...$arguments);
        } else {
            return $this->bs->input($type, ...$arguments);
        }
    }

    protected function group(BaseElement $element, $label = '')
    {
        $help = $element->getAttribute('help');

        return $this->bs->formGroup($element, $label, $help)->showAsRow();
    }

    protected function elementGroup($type, $name, $label = '', $value = null, $attributes = [])
    {
        $element = $this->element($type, $name, $value)->attributes($attributes);

        return $this->group($element, $label);
    }

    protected function value($type, $name, $value)
    {
        $value = $value ?? $this->model->$name ?? request($name);
        $method = $type.'Value';

        return method_exists($this, $method) ? $this->$method($value) : $value;
    }
}
