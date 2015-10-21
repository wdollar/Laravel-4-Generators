<?php namespace Vsch\Generators\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Vsch\Generators\Generators\TranslationsGenerator;
use Vsch\Generators\GeneratorsServiceProvider;
use Vsch\Generators\TranslationFileRewriter;

class TranslationsGeneratorCommand extends BaseGeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate translation files for a model.';

    /**
     * Create a new command instance.
     *
     * @param TranslationsGenerator $generator
     */
    public
    function __construct(TranslationsGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public
    function fire()
    {
        $this->generator->setOptions($this->option());
        $langPath = $this->getPath();
        $scaffold = $this->option('lang');

        if (!$scaffold) {
            $template = $this->option('template');
            $locales = getDirs($langPath, true);
            foreach ($locales as $locale) {
                if ($locale === 'en/') {
                    $path = $this->getPath($locale);
                    $this->printResult($this->generator->make($path, $template, $finalPath), $path, $finalPath);
                }
            }
        } else {
            // add whatever else is specified in the template/lang to appropriate groups
            $langTemplatePath = $scaffold;
            if ($langTemplatePath) {
                $files = getFiles($langTemplatePath, "*.txt", false);
                $config = \Config::get(GeneratorsServiceProvider::PACKAGE, null);
                foreach ($files as $file) {
                    // here we have group.php to map to lang/en/group.php
                    // and create all the lines in the group
                    $group = basename($file, ".txt");
                    $langTemplate = file_get_contents($langTemplatePath . "/" . $file);

                    $locales = getDirs($langPath, true);
                    foreach ($locales as $locale) {
                        if ($locale === 'en/') {
                            // get the file we are to add to
                            $path = $this->getLangFile($group, $locale);

                            $configRewriter = new TranslationFileRewriter();
                            $exportOptions = array_key_exists('export_format', $config) ? TranslationFileRewriter::optionFlags($config['export_format']) : null;

                            $translationModsText = $this->generator->getLangTemplate($langTemplate);

                            $translationMods = <<<PHP
return array(
$translationModsText
);
PHP;
                            try {

                                $newTranslations = eval($translationMods);

                                if ($newTranslations) {
                                    $translations = file_exists($path) ? require($path) : [];
                                    $overwrite = $this->option('overwrite');
                                    $needUpdate = merge_translations($translations, $newTranslations, $overwrite);

                                    if ($needUpdate) {
                                        $configRewriter->parseSource(file_exists($path) ? file_get_contents($path) : '');
                                        $output = $configRewriter->formatForExport($translations, $exportOptions);
                                        if (file_put_contents($path, $output) !== false) {
                                            $this->info("Updated translations from template/lang/$file in $path");
                                        } else {
                                            $this->error("Failed to update translations from template/lang/$file in $path");
                                        }
                                    } else {
                                        $this->info("Did not need to update changes from template/lang/$file in $path");
                                    }
                                } else {
                                    $this->error("failed to evaluate changes from template/lang/$file for $path");
                                }
                            } catch (FatalErrorException $e) {
                                $this->error("failed to evaluate changes from template/lang/$file, exception " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected
    function getPath($locale = null)
    {
        return parent::getSrcPath(self::PATH_LANG, ($locale ? $locale . GeneratorsServiceProvider::replaceModel($this->argument('name'), "{{dash-models}}") . '.php' : ''));
    }

    /**
     * Get the path to the language templates that contain translations to be added to the
     * corresponding translation file
     *
     * @return string
     */
    protected
    function getLangFile($group, $locale = null)
    {
        return parent::getSrcPath(self::PATH_LANG, ($locale ? $locale . $group . '.php' : ''));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected
    function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'Name of the model for which to generate translations.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected
    function getOptions()
    {
        return $this->mergeOptions(array(
            array('path', null, InputOption::VALUE_OPTIONAL, 'Path to the language translations directory.', ''),
            array('template', null, InputOption::VALUE_OPTIONAL, 'Path to template.', GeneratorsServiceProvider::getTemplatePath('translations.txt')),
            array('lang', null, InputOption::VALUE_OPTIONAL, 'Path to template/lang scaffold directory.', ''),
        ));
    }
}
