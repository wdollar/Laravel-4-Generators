<?php

function bug($param)
{
    $name = $param;
    $disabled = false;
    $models = '';
    $readonly = '';
    $inputNarrow = '';

    switch ($param) {
        case 'boolean':
            $element = "{!! Form::checkbox('$name', 1, Input::old('$name'), [$disabled]) !!}";
            $elementFilter = "{!! Form::select('$name', ['' => '&nbsp;', '0' => '0', '1' => '1', ], Input::get('$name'), ['form' => 'filter-$models', 'class' => 'form-control', ]) !!}";
            $useShort = true;
            break;

        case 'date':
        case 'dateTime':
            $element = <<<HTML
<div class="input-group input-group-sm date">
    {!! Form::text('$name', Input::old('$name'), [$readonly'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$models.$name'), ]) !!}
    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
</div>
HTML;
            $elementFilter = <<<HTML
<div class="input-group date">
    {!! Form::text('$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$models.$name'), ]) !!}
    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
</div>
HTML;
            break;


    }

    return $element . $elementFilter;

}
