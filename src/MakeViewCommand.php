<?php namespace MaddHatter\ViewGenerator;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class MakeViewCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view
        {viewname : The name of the view in dotted notation}
        {--e|extend= : (optional) The view this view extends}
        {--r|resource : Create resourceful views at this location}
        {--force : Overwrite existing views}';

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
        $views = $this->viewsToCreate();

        foreach ($views as $name => $path) {
            $directory = dirname($path);

            if ($this->file->exists($path) && ! $this->option('force')) {
                $this->error("A view already exists at {$path}!");
                continue;
            }

            if ( ! $this->file->exists($directory)) {
                $this->file->makeDirectory($directory, 0777, true);
            }

            $this->file->put($path, $this->view($name));
            $this->info("Created a new view at {$path}");
        }
    }

    protected function viewsToCreate()
    {
        if ($this->option('resource')) {
            $folder = $this->argument('viewname');

            return [
                "$folder.index"  => $this->name2Path("$folder.index"),
                "$folder.create" => $this->name2Path("$folder.create"),
                "$folder.edit"   => $this->name2Path("$folder.edit"),
                "$folder.show"   => $this->name2Path("$folder.show"),
            ];
        }

        return [$this->argument('viewname') => $this->name2Path($this->argument('viewname'))];
    }

    protected function name2Path($name)
    {
        return config('view.paths')[0] . '/' . str_replace('.', '/', $name) . '.blade.php';
    }

    protected function view($name)
    {
        if ($extends = $this->extending()) {
            return $this->renderView($extends);
        }

        return $name;
    }

    protected function extending()
    {
        return empty($this->option('extend')) ? false : $this->option('extend');
    }

    protected function renderView($extends)
    {
        $content = [];

        $content[] = "@extends('{$extends}')";

        if ( ! $this->view->exists($extends)) {
            $this->warn("Could not find view: {$extends}");

            return join(PHP_EOL, $content);
        }

        $path   = $this->view->getFinder()->find($extends);
        $parent = $this->file->get($path);

        if (preg_match_all('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $parent, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                switch ($matches[1][$i]) {
                    case 'yield':
                    case 'section':
                        $content[] = PHP_EOL . "@section({$matches[4][$i]})" . PHP_EOL . '@endsection';
                        break;
                    case 'stack':
                        $content[] = PHP_EOL . "@push({$matches[4][$i]})" . PHP_EOL . '@endpush';
                        break;
                }
            }
        }

        return join(PHP_EOL, $content);
    }


}
