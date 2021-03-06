<?php # -*- coding: utf-8 -*-

use Leafo\ScssPhp;

/** @noinspection PhpUndefinedClassInspection */
class RoboFile extends Robo\Tasks
{
    const DOMAIN = 'multilingualpress-site-flags';
    const LANG_PATH = 'languages';

    const DEFAULT_OPTS = [
        'git' => false,
        'phan' => false,
        'skip-lint' => false,
        'skip-tests' => false,
    ];

    /**
     * @param string $release
     * @param array $opts
     *
     * @return Robo\Result|Robo\ResultData|null
     */
    function build(string $release = '', array $opts = self::DEFAULT_OPTS)
    {
        set_error_handler(
            function (int $errno, string $errstr) {
                throw new RuntimeException($errstr, $errno);
            }
        );

        $opts = array_merge(self::DEFAULT_OPTS, $opts);

        $methods = [
            'tests',
            'cleanPublic',
            'buildAssets',
        ];

        $result = null;
        foreach ($methods as $method) {
            /** @var Robo\Result|Robo\ResultData $result */
            $result = ([$this, $method])();
            if ($result && !$result->wasSuccessful()) {
                return $result;
            }
        }

        if ($release) {
            $result = $this->zipRelease($release, $opts);
        }

        restore_error_handler();

        return $result;
    }

    /**
     * @return null|\Robo\Result
     * @throws \Robo\Exception\TaskException
     */
    public function tests()
    {
        return $this->taskExecStack()
            ->stopOnFail()
            ->exec($this->formatBinCommand('phpcs'))
            ->run();
    }

    /**
     * @return Robo\Result
     */
    public function cleanPublic(): Robo\Result
    {
        return $this->taskCleanDir(['public'])->run();
    }

    /**
     * @return Robo\Result
     */
    public function buildAssets(): Robo\Result
    {
        return $this->buildStyles();
    }

    /**
     * @return Robo\Result
     */
    public function buildStyles(): Robo\Result
    {
        $from = __DIR__ . '/resources/scss/';
        $to = __DIR__ . '/public/css/';

        if (!is_dir($to)) {
            $success = $this->taskFilesystemStack()->mkdir($to)->run();
            if (!$success->wasSuccessful()) {
                return $success;
            }
        }

        $success = $this->taskScss([$from . 'frontend.scss' => $to . 'frontend.css'])
            ->setImportPaths($from)
            ->setFormatter(ScssPhp\Formatter\Expanded::class)
            ->run();

        if (!$success->wasSuccessful()) {
            return $success;
        }

        return $this->taskScss([$from . 'frontend.scss' => $to . 'frontend.min.css'])
            ->setFormatter(ScssPhp\Formatter\Crunched::class)
            ->setImportPaths($from)
            ->run();
    }

    /**
     * @param string $release
     * @param array $opts
     *
     * @return Robo\Result|null
     */
    public function zipRelease(string $release, array $opts = ['git' => false])
    {
        if (file_exists(__DIR__ . '/autoload.php')) {
            @unlink(__DIR__ . '/autoload.php');
        }
        $releaseNum = str_replace('.', '-', $this->checkRelease($release));
        $autoloadContent = $this->buildAutoload();

        $autoloadCreated = file_put_contents(__DIR__ . '/autoload.php', $autoloadContent);
        if (!$autoloadCreated) {
            $this->yell(
                "\nCannot create autoload, release creation failed!\n",
                80,
                'red'
            );
        }

        $result = $this->taskPack("multilingualpress-site-flags-{$releaseNum}.zip")
            ->addDir('multilingualpress-site-flags/src', 'src')
            ->addDir('multilingualpress-site-flags/public/css', 'public/css')
            ->addDir('multilingualpress-site-flags/resources/images', 'resources/images')
            ->addDir('multilingualpress-site-flags/languages', 'languages')
            ->addFile('multilingualpress-site-flags/LICENSE', 'LICENSE')
            ->addFile(
                'multilingualpress-site-flags/multilingualpress-site-flags.php',
                'multilingualpress-site-flags.php'
            )
            ->addFile('multilingualpress-site-flags/autoload.php', 'autoload.php')
            ->run();

        if ($result && !$result->wasSuccessful()) {
            return $result;
        }

        if ($opts['git'] ?? false) {
            $result = $this->taskGitStack()
                ->stopOnFail()
                ->add('-A')
                ->commit("v{$releaseNum}")
                ->push('origin', 'master')
                ->tag($releaseNum)
                ->push('origin', $releaseNum)
                ->run();

            if ($result && !$result->wasSuccessful()) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * @param string $release
     *
     * @return string
     */
    private function checkRelease(string $release): string
    {
        $version = new SemanticVersionNumber($release);
        $versionString = (string)$version;

        if ($versionString === SemanticVersionNumber::FALLBACK_VERSION) {
            throw new InvalidArgumentException('Release version must follow Semver.');
        }

        return $versionString;
    }

    /**
     * @return string
     */
    private function buildAutoload(): string
    {
        $autoload = <<<'PHP'
<?php
namespace Inpsyde\MultilingualPress\Flags;

const AUTOLOAD_MAP = [
PHP;
        $autoload .= "\n";
        foreach ($this->dirFiles('src') as $file) {
            $file = str_replace('\\', '/', $file[0]);
            $autoload .= $this->classFromFile($file);
        }

        $autoload .= <<<'PHP'
];

function autoloadClasses($class) {
	$file = AUTOLOAD_MAP[$class] ?? '';
	$file and require_once __DIR__ . $file;
}
spl_autoload_register(__NAMESPACE__ . '\\autoloadClasses');
PHP;
        $autoload .= "\n\n";

        return "{$autoload}\n";
    }

    /**
     * @param string $dir
     * @return Traversable
     */
    private function dirFiles(string $dir): \Traversable
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . "/{$dir}/")),
            '/^.+\.php$/i',
            RecursiveRegexIterator::GET_MATCH
        );
    }

    /**
     * @param string $file
     * @return string
     */
    private function classFromFile(string $file): string
    {
        $parts = explode("src/", $file, 2);
        $name = str_replace('/', '\\', substr($parts[1], 0, -4));
        $name .= "::class => '/src/{$parts[1]}',\n";

        return "    {$name}";
    }

    /**
     * @param string $command
     * @param string $args
     * @return string
     */
    private function formatBinCommand(string $command, string $args = ''): string
    {
        $isWin = defined('PHP_WINDOWS_VERSION_BUILD');

        $prefix = $isWin ? getcwd() . '/' : './';
        $suffix = $isWin ? '.bat' : '';
        $path = str_replace('\\/', DIRECTORY_SEPARATOR, "{$prefix}vendor/bin/{$command}{$suffix}");

        return rtrim("{$path} {$args}");
    }
}

if (!class_exists('SemanticVersionNumber')):
    class SemanticVersionNumber
    {
        const FALLBACK_VERSION = '0.0.0';

        /**
         * @var string
         */
        private $version;

        /**
         * @param string $version
         */
        public function __construct(string $version)
        {
            $this->version = $this->normalize($version);
        }

        /**
         * Returns the version string.
         *
         * @return string
         */
        public function __toString(): string
        {
            return $this->version;
        }

        /**
         * Formats the given number according to the Semantic Versioning specification.
         *
         * @param string $version
         * @return string
         *
         * @see http://semver.org/#semantic-versioning-specification-semver
         */
        private function normalize(string $version): string
        {
            list($number, $preRelease, $meta) = $this->matchSemverPattern($version);

            if (!$number) {
                return self::FALLBACK_VERSION;
            }

            $version = $number;

            if ($preRelease) {
                $version .= "-{$preRelease}";
            }

            if ($meta) {
                $version .= "+{$meta}";
            }

            return $version;
        }

        /**
         * Returns a 3 items array with the 3 parts of SemVer specs, in order:
         * - The numeric part of SemVer specs
         * - The pre-release part of SemVer specs, could be empty
         * - The meta part of SemVer specs, could be empty.
         *
         * @param string $version
         * @return string[]
         */
        private function matchSemverPattern(string $version): array
        {
            $pattern = '~^(?P<numbers>(?:[0-9]+)+(?:[0-9\.]+)?)+(?P<anything>.*?)?$~';
            $matched = preg_match($pattern, $version, $matches);

            if (!$matched) {
                return ['', '', ''];
            }

            $numbers = explode('.', trim($matches['numbers'], '.'));

            // if less than 3 numbers, ensure at least 3 numbers, filling with zero
            $numeric = implode(
                '.',
                array_replace(
                    ['0', '0', '0'],
                    array_slice($numbers, 0, 3)
                )
            );

            // if more than 3 numbers, store additional numbers as build.
            $build = implode('.', array_slice($numbers, 3));

            // if there's nothing else, we already know what to return.
            if (!$matches['anything']) {
                return [$numeric, $build, ''];
            }

            $pre = ltrim($matches['anything'], '-');
            $meta = '';

            // seems we have some metadata.
            if (substr_count($matches['anything'], '+') > 0) {
                $parts = explode('+', $pre);
                // pre is what's before the first +, which could actually be empty
                // when version has meta but not pre-release.
                $pre = array_shift($parts);
                // everything comes after first + is meta.
                // If there were more +, we replace them with dots.
                $meta = $this->sanitizeIdentifier(trim(implode('.', $parts), '-'));
            }

            if ($build) {
                $pre = "{$build}.{$pre}";
            }

            return [$numeric, $this->sanitizeIdentifier($pre), $meta];
        }

        /**
         * Sanitizes given identifier according to SemVer specs.
         * Allow for underscores, replacing them with hyphens.
         *
         * @param string $identifier
         * @return string
         */
        private function sanitizeIdentifier(string $identifier): string
        {
            // the condition will be false for both "" and "0", which are both valid
            // so don't need any replace.
            if ($identifier) {
                $identifier = (string)preg_replace(
                    '~[^a-zA-Z0-9\-\.]~',
                    '',
                    str_replace('_', '-', $identifier)
                );
            }

            return $identifier;
        }
    }
endif;
