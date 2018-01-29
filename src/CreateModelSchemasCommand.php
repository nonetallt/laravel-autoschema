<?php

namespace Nonetallt\LaravelAutoschema;

use Illuminate\Console\Command;

class CreateModelSchemasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a markdown file describing application models.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $output = '';
        $classes = $this->getClasses(config('autoschema.model_directory', app_path()));

        foreach($classes as $class)
        {
            /* Resolve full name of the class */
            $namespace = config('autoschema.model_namespace');
            $className = "$namespace\\$class";

            /* Try get all attributes for the class */
            $attributes = $this->getClassAttributes($className);

            /* Skip unresolvable classes */
            if(! is_array($attributes)) continue;

            /* Output class name */
            $output .= '## ' . $class;    
            
            /* Append table name */
            if(config('autoschema.print_table_name')) {
                $output .= ' (' . (new $className())->getTable() . ')';
            }

            /* Write line change after heading */
            $output .= PHP_EOL;

            $builder = new \MaddHatter\MarkdownTable\Builder();
            $builder->headers(['Attribute', 'Computed', 'Fillable', 'Relation', 'Serialized']);
            $builder->rows((new SchemaRows($className, $attributes))->toArray());

            $output .= $builder->render();
            $output .= str_repeat(PHP_EOL, 2);
        }

        /* Write the output */
        $filepath = config('autoschema.output_path');
        $handle = fopen($filepath, 'w');
        fwrite($handle, $output);
        fclose($handle);

        $this->info("Schema created at '$filepath'");
    }

    private function getClassAttributes(string $className)
    {
        /* Check if class exists */
        if(! class_exists($className)) throw new \Exception("Could no find class '$className'");

        /* Check that class is an eloquent model */ 
        if(! is_subclass_of($className,'Illuminate\Database\Eloquent\Model')) return false;

        /* Dynamically generate a new class instance */
        $model = new $className();

        /* Make sure there are no duplicates if database attributes have defined accessor */
        return  [
            'columns'   => $this->getColumns($model),
            'accessors' => $this->getAccessorAttributes($className),
            'relations' => $this->getRelations($className)
            /* $this->getTimestamps($model) */
        ];
    }

    private function getColumns($model)
    {
        $columns = \Schema::getColumnListing($model->getTable());
        return $columns;
    }

    /* Already listed by getColumns */
    private function getTimestamps($model)
    {
        /* if(is_a($model, 'App\BounceMessage')); */
        if($model->usesTimestamps()) return [
            'created_at',
            'updated_at'
        ];
        return [];
    }

    private function getRelations(string $className)
    {
        /* Find all methods for the class */
        $methods = (new \ReflectionClass($className))->getMethods();

        /* Get all methods that are defined by the class and not parents */
        /* Check that the method has 'relation' docblock annotation */
        $methods = collect($methods)->filter(function($method) use ($className){
            return $method->class === $className && $this->methodIsRelation($className, $method->name);
        })
        ->map(function($method) use ($className){
            /* Apply snake case if neccesary */
            return $this->applySnakeCase($className, $method->name);
        })
        ->toArray();

        return $methods;
    }

    private function methodIsRelation(string $className, $method)
    {
        try {
            $reader = new \DocBlockReader\Reader($className, $method, 'method');
            $relation = $reader->getParameter('relation');
            return ! is_null($relation);
        }
        catch(\ReflectionException $e) {
        }
        return false;
    }

    private function applySnakeCase(string $className, string $subject)
    {
        /* Change to snake case if the option is used for this class */
        if($className::$snakeAttributes) $subject = snake_case($subject);

        return $subject;
    }

    private function getAccessorAttributes(string $className)
    {
        $attributes = [];

        /* Find methods matching getXAttribute */
        foreach(get_class_methods($className) as $method)
        {
            /* Make sure that the whole signature is matched */
            if(preg_match('|^get.+?Attribute$|', $method) === 1) {

                /* Get the name of the attribute by removing other parts*/
                $name = str_replace('get', '', str_replace('Attribute', '', $method));

                /* Lowercase first letter */
                $name = lcfirst($name);

                $name = $this->applySnakeCase($className, $name);

                $attributes[] = $name;
            }
        }
        return $attributes;
    }

    /**
     * Find all .php file extension files within the given path
     */
    private function getClasses(string $path)
    {
        $classes = [];
        $files = scandir($path);

        /* Only handle .php files */
        foreach($files as $file)
        {
            $expected = '.php';
            $extension = substr($file, -strlen($expected));

            if( $extension !== $expected ) continue;

            /* Get the expected name of the class */
            $classes[] = str_replace($expected, '', $file);
        }
        return $classes;
    }
}
