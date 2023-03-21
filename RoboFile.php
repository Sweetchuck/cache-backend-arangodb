<?php

declare(strict_types = 1);

use League\Container\Container as LeagueContainer;
use NuvoleWeb\Robo\Task\Config\loadTasks as ConfigLoader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\Tasks;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Sweetchuck\Robo\PhpMessDetector\PhpmdTaskLoader;
use Sweetchuck\Robo\PHPUnit\PHPUnitTaskLoader;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoboFile extends Tasks implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ConfigLoader;
    use GitTaskLoader;
    use PhpcsTaskLoader;
    use PhpmdTaskLoader;
    use PHPUnitTaskLoader;

    /**
     * @var array<string, mixed>
     */
    protected array $composerInfo = [];

    protected string $packageVendor = '';

    protected string $packageName = '';

    protected string $binDir = 'vendor/bin';

    protected string $gitHook = '';

    protected string $envVarNamePrefix = '';

    /**
     * Allowed values: dev, ci, prod.
     */
    protected string $environmentType = '';

    /**
     * Allowed values: local, jenkins, travis, circleci.
     */
    protected string $environmentName = '';

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
        $this
            ->initComposerInfo()
            ->initEnvVarNamePrefix()
            ->initEnvironmentTypeAndName();
    }

    /**
     * @hook pre-command @initLintReporters
     */
    public function initLintReporters(): void
    {
        $container = $this->getContainer();
        if (!($container instanceof LeagueContainer)) {
            return;
        }

        foreach (BaseReporter::getServices() as $name => $class) {
            if ($container->has($name)) {
                continue;
            }

            $container
                ->add($name, $class)
                ->setShared(false);
        }
    }

    /**
     * Git "pre-commit" hook callback.
     *
     * @command githook:pre-commit
     *
     * @hidden
     *
     * @initLintReporters
     */
    public function githookPreCommit(): CollectionBuilder
    {
        $this->gitHook = 'pre-commit';

        return $this
            ->collectionBuilder()
            ->addTask($this->taskComposerValidate())
            ->addTask($this->getTaskPhpcsLint())
            ->addTask($this->getTaskPhpunitRun());
    }

    /**
     * Run code style checkers.
     *
     * @initLintReporters
     */
    public function lint(): TaskInterface
    {
        return $this
            ->collectionBuilder()
            ->addTask($this->taskComposerValidate())
            ->addTask($this->getTaskPhpcsLint());
    }

    /**
     * @initLintReporters
     */
    public function lintPhpcs(): TaskInterface
    {
        return $this->getTaskPhpcsLint();
    }

    /**
     * @initLintReporters
     */
    public function lintPhpmd(): TaskInterface
    {
        return $this->getTaskPhpmdLint();
    }

    /**
     * @initLintReporters
     */
    public function lintPsalm(): TaskInterface
    {
        return $this->getTaskPsalmLint();
    }

    /**
     * @initLintReporters
     */
    public function lintPhpstan(): TaskInterface
    {
        return $this->getTaskPhpstanAnalyze();
    }

    /**
     * Run the Robo unit tests.
     */
    public function test(array $suiteNames): TaskInterface
    {
        return $this->getTaskPhpunitRun($suiteNames);
    }

    protected function errorOutput(): ?OutputInterface
    {
        $output = $this->output();

        return ($output instanceof ConsoleOutputInterface)
            ? $output->getErrorOutput() : $output;
    }

    protected function initEnvVarNamePrefix(): static
    {
        $this->envVarNamePrefix = strtoupper(
            str_replace(
                '-',
                '_',
                $this->packageName,
            ),
        );

        return $this;
    }

    protected function initEnvironmentTypeAndName(): static
    {
        $this->environmentType = (string) getenv($this->getEnvVarName('environment_type'));
        $this->environmentName = (string) getenv($this->getEnvVarName('environment_name'));

        if (!$this->environmentType) {
            if (getenv('CI') === 'true') {
                // Travis, GitLab and CircleCI.
                $this->environmentType = 'ci';
            } elseif (getenv('JENKINS_HOME')) {
                $this->environmentType = 'ci';
                if (!$this->environmentName) {
                    $this->environmentName = 'jenkins';
                }
            }
        }

        if (!$this->environmentName && $this->environmentType === 'ci') {
            if (getenv('GITLAB_CI') === 'true') {
                $this->environmentName = 'gitlab';
            } elseif (getenv('TRAVIS') === 'true') {
                $this->environmentName = 'travis';
            } elseif (getenv('CIRCLECI') === 'true') {
                $this->environmentName = 'circleci';
            }
        }

        if (!$this->environmentType) {
            $this->environmentType = 'dev';
        }

        if (!$this->environmentName) {
            $this->environmentName = 'local';
        }

        return $this;
    }

    protected function getEnvVarName(string $name): string
    {
        return "{$this->envVarNamePrefix}_" . strtoupper($name);
    }

    protected function getPhpExecutable($key): array
    {
        $executable = $this->getConfig()->get("php.executables.$key") ?: [];
        $definition = array_replace_recursive(
            [
                'envVars' => [],
                'binary' => 'php',
                'args' => [],
            ],
            $executable ?? [],
        );

        $argFilter = function (array $value): bool {
            return !empty($value['enabled']);
        };
        $argComparer = function (array $a, array $b): int {
            return ($a['weight'] ?? 99) <=> ($b['weight'] ?? 99);
        };
        $definition['envVars'] = array_filter($definition['envVars']);
        $definition['args'] = array_filter($definition['args'], $argFilter);
        uasort($definition['args'], $argComparer);

        return $definition;
    }

    protected function initComposerInfo(): static
    {
        $composerFileName = getenv('COMPOSER') ?: 'composer.json';
        if ($this->composerInfo || !is_readable($composerFileName)) {
            return $this;
        }

        $this->composerInfo = array_replace_recursive(
            [
                'config' => [
                    'bin-dir' => 'vendor/bin',
                ],
            ],
            json_decode(file_get_contents($composerFileName), true),
        );

        [$this->packageVendor, $this->packageName] = explode('/', $this->composerInfo['name']);

        if (!empty($this->composerInfo['config']['bin-dir'])) {
            $this->binDir = $this->composerInfo['config']['bin-dir'];
        }

        return $this;
    }

    protected function getTaskPhpcsLint(): CollectionBuilder
    {
        $options = [
            'failOn' => 'warning',
            'lintReporters' => [
                'lintVerboseReporter' => null,
            ],
        ];

        if ($this->environmentType === 'ci'
            && $this->environmentName === 'jenkins') {
            $options['failOn'] = 'never';
            $options['lintReporters']['lintCheckstyleReporter'] = $this
                ->getContainer()
                ->get('lintCheckstyleReporter')
                ->setDestination('tests/_output/machine/checkstyle/phpcs.psr2.xml');
        }

        if ($this->gitHook === 'pre-commit') {
            return $this
                ->collectionBuilder()
                ->addTask($this
                    ->taskPhpcsParseXml()
                    ->setAssetNamePrefix('phpcsXml.'))
                ->addTask($this
                    ->taskGitReadStagedFiles()
                    ->setCommandOnly(true)
                    ->setWorkingDirectory('.')
                    ->deferTaskConfiguration('setPaths', 'phpcsXml.files'))
                ->addTask($this
                    ->taskPhpcsLintInput($options)
                    ->deferTaskConfiguration('setFiles', 'files')
                    ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
        }

        return $this->taskPhpcsLintFiles($options);
    }

    protected function getTaskPhpmdLint(): CollectionBuilder
    {
        $ruleSetName = 'custom';

        $task = $this
            ->taskPhpmdLintFiles()
            ->setInputFile("./rulesets/$ruleSetName.include-pattern.txt")
            ->setRuleSetFileNames([$ruleSetName])
            ->setOutput($this->output());

        $excludeFileName = "./rulesets/$ruleSetName.exclude-pattern.txt";
        if (file_exists($excludeFileName)) {
            $task->addExcludePathsFromFile($excludeFileName);
        }

        return $task;
    }

    protected function getTaskPsalmLint(): TaskInterface
    {
        $cmdPattern = '%s --report=%s';
        $cmdArgs = [
            escapeshellcmd("{$this->binDir}/psalm"),
            escapeshellarg('reports/human/checkstyle/psalm.txt')
        ];

        return $this->taskExec(vsprintf($cmdPattern, $cmdArgs));
    }

    protected function getTaskPhpstanAnalyze(): TaskInterface
    {
        $cmdPattern = '%s --error-format=%s > %s';
        $cmdArgs = [
            escapeshellcmd("{$this->binDir}/phpstan"),
            escapeshellarg('table'),
            escapeshellarg('reports/human/checkstyle/phpstan.txt'),
        ];

        return $this->taskExec(vsprintf($cmdPattern, $cmdArgs));
    }

    protected function getTaskPhpunitRun(array $suiteNames = []): CollectionBuilder
    {
        $phpExecutables = array_filter(
            (array) $this->getConfig()->get('php.executables'),
            new ArrayFilterEnabled(),
        );

        $cb = $this->collectionBuilder();
        foreach ($phpExecutables as $php) {
            $cb->addTask($this->getTaskPhpunitRunSingle($suiteNames, $php));
        }

        return $cb;
    }

    protected function getTaskPhpunitRunSingle(array $suiteNames, array $php): TaskInterface
    {
        $binDir = $this->composerInfo['config']['bin-dir'];

        return $this
            ->taskPHPUnitRun()
            ->setEnvVars($php['envVars'] ?? [])
            ->setColors('always')
            ->setHideStdOutput(false)
            ->setPhpExecutable($php['command'])
            ->setPhpunitExecutable("$binDir/phpunit")
            ->setTestSuite($suiteNames);
    }
}
