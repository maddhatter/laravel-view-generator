<?php namespace MaddHatter\ViewGenerator;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class MakeView extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view
        {viewname : The name of the view in dotted notation}
        {--e|extend= : (optional) The view this view extends}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an empty blade view.';

    /**
     * @var File
     */
    private $file;

    /**
     * @var Factory
     */
    private $view;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $file
     * @param Factory    $viewFactory
     */
    public function __construct(Filesystem $file, Factory $viewFactory)
    {
        $this->file = $file;
        $this->view = $viewFactory;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path      = $this->getFilePath();
        $directory = dirname($path);

        if ($this->file->exists($path)) {
            $this->error("A view already exists at {$path}!");

            return false;
        }

        if ( ! $this->file->exists($directory)) {
            $this->file->makeDirectory($directory, 0777, true);
        }

        $this->file->put($path, $this->getViewContents());
        $this->info("Created a new view at {$path}");
    }

    protected function getFilePath()
    {
        return config('view.paths')[0] . '/' . str_replace('.', '/', $this->argument('viewname')) . '.blade.php';
    }

    protected function getViewContents()
    {

        if ($extends = $this->extending()) {
            $content = [];

            $content[] = "@extends('{$extends}')";

            $sections = $this->getSections($extends);

            foreach ($sections as $section) {
                $content[] = PHP_EOL . "@section({$section})" . PHP_EOL . '@endsection';
            }

            return join(PHP_EOL, $content);

        }

        return $this->argument('viewname');
    }

    protected function extending()
    {
        return empty($this->option('extend')) ? false : $this->option('extend');
    }


    private function getSections($parent)
    {
        $sections = [];

        if ( ! $this->view->exists($parent)) {
            $this->warn("Could not find view: {$parent}");
            return $sections;
        }

        $path    = $this->view->getFinder()->find($parent);
        $content = $this->file->get($path);

        if (preg_match_all('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $content, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                if ($matches[1][$i] == 'yield') {
                    $sections[] = $matches[4][$i];
                }
            }
        }

        return $sections;
    }


}
