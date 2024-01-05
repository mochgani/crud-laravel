<?php

namespace Mochgani\CrudLaravel\Services;


use Illuminate\Support\Facades\File;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Contracts\Foundation\Application;

class MakeViewsService
{
    use InteractsWithIO;

    public PathsAndNamespacesService $pathsAndNamespacesService;
    public function __construct(
        PathsAndNamespacesService $pathsAndNamespacesService,
        ConsoleOutput $consoleOutput,
        Application $application
    )
    {
        $this->pathsAndNamespacesService = $pathsAndNamespacesService;
        $this->output = $consoleOutput;
        $this->laravel = $application->getNamespace();
    }

    public function createDirectoryViews($namingConvention)
    {
        $directoryName = $this->pathsAndNamespacesService->getRealpathBaseCustomViews($namingConvention);
        // if the directory doesn't exist we create it
        if (!File::isDirectory($directoryName))
        {
            File::makeDirectory($directoryName, 0755, true);
            $this->line("<info>Created views directory:</info> ".$namingConvention['plural_low_name']);
        }
        else
            $this->error('Views directory '.$namingConvention['plural_low_name'].' already exists');
    }

    public function replaceContentControllerStub($namingConvention, $laravelNamespace)
    {
        $controllerStub = File::get($this->pathsAndNamespacesService->getControllerStubPath());
        $controllerStub = str_replace('DummyClass', $namingConvention['plural_name'].'Controller', $controllerStub);
        $controllerStub = str_replace('DummyModel', $namingConvention['singular_name'], $controllerStub);
        $controllerStub = str_replace('DummyVariableSing', $namingConvention['singular_low_name'], $controllerStub);
        $controllerStub = str_replace('DummyVariable', $namingConvention['plural_low_name'], $controllerStub);
        $controllerStub = str_replace('DummyNamespace', $this->pathsAndNamespacesService->getDefaultNamespaceController($laravelNamespace), $controllerStub);
        $controllerStub = str_replace('DummyRootNamespace', $laravelNamespace, $controllerStub);
        return $controllerStub;
    }

    public function findAndReplaceControllerPlaceholderColumns($columns, $controllerStub, $namingConvention)
    {
        $cols='';
        foreach ($columns as $column)
        {
            $type     = explode(':', trim($column));
            $column   = $type[0];

            // our placeholders
            $cols .= str_repeat("\t", 2).'DummyCreateVariableSing$->'.trim($column).'=$request->input(\''.trim($column).'\');'."\n";
        }

        // we replace our placeholders
        $controllerStub = str_replace('DummyUpdate', $cols, $controllerStub);
        $controllerStub = str_replace('DummyCreateVariable$', '$'.$namingConvention['plural_low_name'], $controllerStub);
        $controllerStub = str_replace('DummyCreateVariableSing$', '$'.$namingConvention['singular_low_name'], $controllerStub);

        return $controllerStub;
    }

    public function findAndReplaceIndexViewPlaceholderColumns($columns, $templateViewsDirectory, $namingConvention, $separateStyleAccordingToActions, $withLivewire, $columnInSearch)
    {
        $thIndex=$indexView='';
        foreach ($columns as $column)
        {
            $type      = explode(':', trim($column));
            $column    = $type[0];

            // our placeholders
            if(count($type)==4 && $type[1]=='relasi'){
                $thIndex    .=str_repeat("\t", 4)."<th>".trim(ucfirst($type[3]))."</th>\n";
            } else {
                $thIndex    .=str_repeat("\t", 4)."<th>".trim($column)."</th>\n";
            }

            if($column == $columnInSearch)
                $indexView  .=str_repeat("\t", 5).'<td>{!! $this->search ? $this->highlightTitle(DummyCreateVariableSing$->'.$columnInSearch.') : DummyCreateVariableSing$->'.$columnInSearch.' !!}</td>'."\n";
            else{
                if(count($type)==4 && $type[1]=='relasi'){
                    $indexView  .=str_repeat("\t", 5)."<td>{{ DummyCreateVariableSing$->".trim($type[2])."->".trim($type[3])." }}</td>\n";
                } else {
                    $indexView  .=str_repeat("\t", 5)."<td>{{ DummyCreateVariableSing$->".trim($column)." }}</td>\n";
                }
            }
        }

        $indexStub =  $withLivewire
                    ? File::get($this->pathsAndNamespacesService->getCrudgenViewsStubCustom($templateViewsDirectory).DIRECTORY_SEPARATOR.'livewire'.DIRECTORY_SEPARATOR.'index-datatable.stub')
                    : File::get($this->pathsAndNamespacesService->getCrudgenViewsStubCustom($templateViewsDirectory).DIRECTORY_SEPARATOR.'index.stub');

        $indexStub = str_replace('DummyCreateVariable$', '$'.$namingConvention['plural_low_name'], $indexStub);
        $indexStub = str_replace('DummyCreateVariableSing$', '$'.$namingConvention['singular_low_name'], $indexStub);
        $indexStub = str_replace('DummyHeaderTable', $thIndex, $indexStub);
        $indexStub = str_replace('DummyIndexTable', $indexView, $indexStub);
        $indexStub = str_replace('DummyCreateVariableSing$', '$'.$namingConvention['singular_low_name'], $indexStub);
        $indexStub = str_replace('DummyVariable', $namingConvention['plural_low_name'], $indexStub);
        $indexStub = str_replace('DummyExtends', $separateStyleAccordingToActions['index']['extends'], $indexStub);
        $indexStub = str_replace('DummySection', $separateStyleAccordingToActions['index']['section'], $indexStub);

        return $indexStub;
    }

    public function findAndReplaceIndexViewPlaceholderLivewire($templateViewsDirectory, $namingConvention, $separateStyleAccordingToActions, $withLivewire)
    {
        if($withLivewire)
        {
            $indexStub = File::get($this->pathsAndNamespacesService->getCrudgenViewsStubCustom($templateViewsDirectory).DIRECTORY_SEPARATOR.'index-livewire.stub');
            $indexStub = str_replace('{{nameSingLower}}', $namingConvention['singular_low_name'], $indexStub);
            $indexStub = str_replace('DummyExtends', $separateStyleAccordingToActions['index']['extends'], $indexStub);
            $indexStub = str_replace('DummySection', $separateStyleAccordingToActions['index']['section'], $indexStub);
            return $indexStub;
        }
    }

    public function findAndReplaceCreateViewPlaceholderColumns($columns, $templateViewsDirectory, $namingConvention, $separateStyleAccordingToActions)
    {
        $formCreate='';
        foreach ($columns as $column)
        {
            $type      = explode(':', trim($column));
            $sql_type  = (count($type)==2) ? $type[1] : 'string';
            $column    = $type[0];
            $typeHtml = $this->getHtmlType($sql_type);

            if(count($type)==4 && $type[1]=='relasi'){
                // our placeholders
                $formCreate .=str_repeat("\t", 2).'<div class="mb-3">'."\n";
                $formCreate .=str_repeat("\t", 3).'{{ Form::label(\''.trim($column).'\', \''.ucfirst(trim($type[2])).'\', [\'class\'=>\'form-label\']) }}'."\n";
                $formCreate .=str_repeat("\t", 3).'<select class=\'form-control\' name=\''.trim($column).'\' required>'."\n";
                $formCreate .=str_repeat("\t", 4).'<option value=\'\' selected disabled>- Choose '.ucfirst(trim($type[3])).' -</option>'."\n";
                $formCreate .=str_repeat("\t", 4).'@php $'.trim($type[2]).'s = App\\Models\\'.ucfirst(trim($type[2])).'::all()->sortBy(\'created_at\'); @endphp'."\n";
                $formCreate .=str_repeat("\t", 4).'@foreach($'.trim($type[2]).'s as $item)'."\n";
                $formCreate .=str_repeat("\t", 5).'<option value=\'{{ $item->id }}\'>{{ $item->'.trim($type[3]).' }}</option>'."\n";
                $formCreate .=str_repeat("\t", 4).'@endforeach'."\n";
                $formCreate .=str_repeat("\t", 3).'</select>'."\n";
                $formCreate .=str_repeat("\t", 2).'</div>'."\n";
            } else if(count($type)==3 && $type[1]=='enum'){
                $enum = explode('_', trim($type[2]));
                $enum_val = "";
                foreach($enum as $val){
                    $enum_val .= '\'' . $val . '\' => \'' . $val .'\', ';
                }
                // our placeholders
                $formCreate .=str_repeat("\t", 2).'<div class="mb-3">'."\n";
                $formCreate .=str_repeat("\t", 3).'{{ Form::label(\''.trim($column).'\', \''.ucfirst(trim($column)).'\', [\'class\'=>\'form-label\']) }}'."\n";
                $formCreate .=str_repeat("\t", 3).'{{ Form::select(\''.trim($column).'\',array('.substr($enum_val,0,-2).'), null, array(\'class\' => \'form-control\')) }}'."\n";
                $formCreate .=str_repeat("\t", 2).'</div>'."\n";
            } else {
                // our placeholders
                $formCreate .=str_repeat("\t", 2).'<div class="mb-3">'."\n";
                $formCreate .=str_repeat("\t", 3).'{{ Form::label(\''.trim($column).'\', \''.ucfirst(trim($column)).'\', [\'class\'=>\'form-label\']) }}'."\n";
                $formCreate .=str_repeat("\t", 3).'{{ Form::'.$typeHtml.'(\''.trim($column).'\', null, array(\'class\' => \'form-control\')) }}'."\n";
                $formCreate .=str_repeat("\t", 2).'</div>'."\n";
            }
        }

        $createStub = File::get($this->pathsAndNamespacesService->getCrudgenViewsStubCustom($templateViewsDirectory).DIRECTORY_SEPARATOR.'create.stub');
        $createStub = str_replace('DummyVariable', $namingConvention['plural_low_name'], $createStub);
        $createStub = str_replace('DummyFormCreate', $formCreate, $createStub);
        $createStub = str_replace('DummyExtends', $separateStyleAccordingToActions['create']['extends'], $createStub);
        $createStub = str_replace('DummySection', $separateStyleAccordingToActions['create']['section'], $createStub);
        return $createStub;
    }

    public function findAndReplaceShowViewPlaceholderColumns($templateViewsDirectory, $namingConvention, $separateStyleAccordingToActions)
    {
        $showStub = File::get($this->pathsAndNamespacesService->getCrudgenViewsStubCustom($templateViewsDirectory).DIRECTORY_SEPARATOR.'show.stub');
        $showStub = str_replace('DummyCreateVariableSing$', '$'.$namingConvention['singular_low_name'], $showStub);
        $showStub = str_replace('DummyExtends', $separateStyleAccordingToActions['show']['extends'], $showStub);
        $showStub = str_replace('DummySection', $separateStyleAccordingToActions['show']['section'], $showStub);
        return $showStub;
    }

    public function findAndReplaceEditViewPlaceholderColumns($columns, $templateViewsDirectory, $namingConvention, $separateStyleAccordingToActions)
    {
        $formEdit='';
        foreach ($columns as $column)
        {
            $type      = explode(':', trim($column));
            $sql_type  = (count($type)==2) ? $type[1] : 'string';
            $column    = $type[0];
            $typeHtml = $this->getHtmlType($sql_type);
            
            if(count($type)==4 && $type[1]=='relasi'){
                // our placeholders
                $formEdit .=str_repeat("\t", 2).'<div class="mb-3">'."\n";
                $formEdit .=str_repeat("\t", 3).'{{ Form::label(\''.trim($column).'\', \''.ucfirst(trim($type[2])).'\', [\'class\'=>\'form-label\']) }}'."\n";
                $formEdit .=str_repeat("\t", 3).'<select class=\'form-control\' name=\''.trim($column).'\' required>'."\n";
                $formEdit .=str_repeat("\t", 4).'<option value=\'\' selected disabled>- Choose '.ucfirst(trim($type[3])).' -</option>'."\n";
                $formEdit .=str_repeat("\t", 4).'@php $'.trim($type[2]).'s = App\\Models\\'.ucfirst(trim($type[2])).'::all()->sortBy(\'created_at\'); @endphp'."\n";
                $formEdit .=str_repeat("\t", 4).'@foreach($'.trim($type[2]).'s as $item)'."\n";
                $formEdit .=str_repeat("\t", 5).'<option value=\'{{ $item->id }}\' {{ ($'.$namingConvention['singular_low_name'].'->'.trim($column).' == $item->id)?\'selected\':\'\' }} >{{ $item->'.trim($type[3]).' }}</option>'."\n";
                $formEdit .=str_repeat("\t", 4).'@endforeach'."\n";
                $formEdit .=str_repeat("\t", 3).'</select>'."\n";
                $formEdit .=str_repeat("\t", 2).'</div>'."\n";
            } else if(count($type)==3 && $type[1]=='enum'){
                $enum = explode('_', trim($type[2]));
                $enum_val = "";
                foreach($enum as $val){
                    $enum_val .= '\'' . $val . '\' => \'' . $val .'\', ';
                }
                // our placeholders
                $formEdit .=str_repeat("\t", 2).'<div class="mb-3">'."\n";
                $formEdit .=str_repeat("\t", 3).'{{ Form::label(\''.trim($column).'\', \''.ucfirst(trim($column)).'\', [\'class\'=>\'form-label\']) }}'."\n";
                $formEdit .=str_repeat("\t", 3).'{{ Form::select(\''.trim($column).'\',array('.substr($enum_val,0,-2).'), null, array(\'class\' => \'form-control\')) }}'."\n";
                $formEdit .=str_repeat("\t", 2).'</div>'."\n";
            } else {
                // our placeholders
                $formEdit .=str_repeat("\t", 2).'<div class="mb-3">'."\n";
                $formEdit .=str_repeat("\t", 3).'{{ Form::label(\''.trim($column).'\', \''.ucfirst(trim($column)).'\', [\'class\'=>\'form-label\']) }}'."\n";
                $formEdit .=str_repeat("\t", 3).'{{ Form::'.$typeHtml.'(\''.trim($column).'\', null, array(\'class\' => \'form-control\')) }}'."\n";
                $formEdit .=str_repeat("\t", 2).'</div>'."\n";
            }
        }

        $editStub = File::get($this->pathsAndNamespacesService->getCrudgenViewsStubCustom($templateViewsDirectory).DIRECTORY_SEPARATOR.'edit.stub');
        $editStub = str_replace('DummyCreateVariableSing$', '$'.$namingConvention['singular_low_name'], $editStub);
        $editStub = str_replace('DummyVariable', $namingConvention['plural_low_name'], $editStub);
        $editStub = str_replace('DummyFormCreate', $formEdit, $editStub);
        $editStub = str_replace('DummyExtends', $separateStyleAccordingToActions['edit']['extends'], $editStub);
        $editStub = str_replace('DummySection', $separateStyleAccordingToActions['edit']['section'], $editStub);
        return $editStub;
    }

    public function createFileOrError($namingConvention, $contentFile, $fileName, $withLivewire=false)
    {
        $path = $withLivewire
        ? $this->pathsAndNamespacesService->getRealpathBaseCustomLivewireViews($namingConvention).DIRECTORY_SEPARATOR.$fileName
        : $this->pathsAndNamespacesService->getRealpathBaseCustomViews($namingConvention).DIRECTORY_SEPARATOR.$fileName;

        if(!File::exists($path))
        {
            File::put($path, $contentFile);
            $this->line("<info>Created View:</info> ".$fileName);
        }
        else
            $this->error('View '.$fileName.' already exists');
    }

    private function getHtmlType($sql_type)
    {
        $conversion =
        [
            'string'  => 'text',
            'text'    => 'textarea',
            'integer' => 'number',
            'uuid'  => 'text',
            'date'  => 'date',
            'enum'  => 'select'
        ];
        return (isset($conversion[$sql_type]) ? $conversion[$sql_type] : 'string');
    }
}
