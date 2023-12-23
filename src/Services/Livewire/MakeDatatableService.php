<?php

namespace Mochgani\CrudLaravel\Services\Livewire;


use Illuminate\Support\Facades\File;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Contracts\Foundation\Application;
use Mochgani\CrudLaravel\Services\MakeGlobalService;
use Mochgani\CrudLaravel\Services\PathsAndNamespacesService;

class MakeDatatableService
{
    use InteractsWithIO;

    public PathsAndNamespacesService $pathsAndNamespacesService;
    public MakeGlobalService $makeGlobalService;

    public $customDirBlade = 'backend.pages.';

    public function __construct(
        PathsAndNamespacesService $pathsAndNamespacesService,
        ConsoleOutput $consoleOutput,
        Application $application,
        MakeGlobalService $makeGlobalService
    )
    {
        $this->pathsAndNamespacesService = $pathsAndNamespacesService;
        $this->output = $consoleOutput;
        $this->laravel = $application->getNamespace();
        $this->makeGlobalService = $makeGlobalService;
    }

    public function replaceContentDatatableStub($namingConvention, $laravelNamespace, $columnInSearch, $columns)
    {
        


        $datatableStub = File::get($this->pathsAndNamespacesService->getDatatableStubPath());
        $datatableStub = str_replace('DummyClass', $namingConvention['singular_name'].'Datatable', $datatableStub);
        $datatableStub = str_replace('DummyModel', $namingConvention['singular_name'], $datatableStub);
        $datatableStub = str_replace('DummyVariable', $namingConvention['plural_low_name'], $datatableStub);

        $cols='';
        foreach ($columns as $column)
        {
            $type      = explode(':', trim($column));
            $sql_type  = (count($type)==2) ? $type[1] : 'string';
            $column    = $type[0];
            $typeHtml = $this->getHtmlType($sql_type);

            if(count($type)==4 && $type[1]=='relasi'){
                $cols .= 'with(\''.$type[2].'\')->';
            } else {
                $cols .= '';
            }
        }

        $datatableStub = str_replace('DummyRelasi', $cols, $datatableStub);
        $datatableStub = str_replace('DummyNamespace', $this->pathsAndNamespacesService->getDefaultNamespaceDatatable($laravelNamespace), $datatableStub);
        $datatableStub = str_replace('DummyRootNamespace', $laravelNamespace, $datatableStub);
        $datatableStub = str_replace('DummyCreateVariable$', '$'.$namingConvention['plural_low_name'], $datatableStub);
        $datatableStub = str_replace('{{name-component}}', $namingConvention['singular_low_name'], $datatableStub);
        $datatableStub = str_replace('{{directory-views}}', $namingConvention['plural_low_name'], $datatableStub);
        $datatableStub = str_replace('{{column-in-search}}', $columnInSearch, $datatableStub);
        $datatableStub = str_replace('DummyBaseDir', $this->customDirBlade, $datatableStub);

        return $datatableStub;
    }

    public function createDatatableFile($pathNewDatatable, $datatableStub, $namingConvention)
    {
        if(!File::exists($pathNewDatatable))
        {
            File::put($pathNewDatatable, $datatableStub);
            $this->line("<info>Created Datatable:</info> ".$namingConvention['singular_name']);
        }
        else
            $this->error('Datatable '.$namingConvention['singular_name'].' already exists');
    }

    public function makeCompleteDatatableFile($namingConvention, $laravelNamespace, $columnInSearch, $columns)
    {
        $datatableStub = $this->replaceContentDatatableStub($namingConvention, $laravelNamespace, $columnInSearch, $columns);

        if (!File::isDirectory($this->pathsAndNamespacesService->getRealpathBaseDatatable()))
        {
            File::makeDirectory($this->pathsAndNamespacesService->getRealpathBaseDatatable(), 0755, true);
            $this->line("<info>Created Livewire directory</info>");
        }

        // if our datatable doesn't exists we create it
        $pathNewDatatable = $this->pathsAndNamespacesService->getRealpathBaseCustomDatatable($namingConvention);
        $this->createDatatableFile($pathNewDatatable, $datatableStub, $namingConvention);
    }
}
