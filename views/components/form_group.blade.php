<div class="row form-group {{ $class ?? '' }}">
  <label class="col-form-label col-sm-4 text-sm-right {{ $class_left ?? '' }}">
    {{ $label ?? '' }}
  </label>
  <div class="col-sm-8 col-md-6 {{ $class_right ?? '' }}">
    {{ $slot }}
  </div>
</div>
