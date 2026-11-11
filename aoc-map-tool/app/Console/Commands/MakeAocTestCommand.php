<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

class MakeAocTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:aoc-test
                            {path : Path to the source class relative to the project root, e.g. app/Facades/CacheKeysHelper.php}
                            {--integration : Generate an Integration test instead of Unit}
                            {--feature : Generate a Feature test instead of Unit}
                            {--bare : Extend Tests\UnitTestCase (no app boot) instead of Tests\TestCase — Unit only}
                            {--force : Overwrite the test file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a test class in the correct tests/{Unit,Integration,Feature} folder, mirroring app/ structure (see tests.md)';

    public function handle(): int
    {
        $suite = $this->resolveSuite();

        if ($suite === null) {
            return 1;
        }

        $path = ltrim(str_replace('\\', '/', $this->argument('path')), './');

        if (!Str::startsWith($path, 'app/') || !Str::endsWith($path, '.php')) {
            $this->error('Path must point to a .php file under app/, e.g. app/Facades/CacheKeysHelper.php');

            return 1;
        }

        if (!file_exists(base_path($path))) {
            $this->error("File not found: {$path}");

            return 1;
        }

        $relative = Str::of($path)->after('app/')->beforeLast('.php')->value();
        $fqcn = 'App\\' . str_replace('/', '\\', $relative);
        $shortName = class_basename($fqcn);

        $dirRaw = dirname($relative);
        $dirRaw = $dirRaw === '.' ? '' : $dirRaw;
        $dirNamespace = $dirRaw === '' ? '' : '\\' . str_replace('/', '\\', $dirRaw);
        $dirPath = $dirRaw === '' ? '' : '/' . $dirRaw;

        $testNamespace = 'Tests\\' . $suite . $dirNamespace;
        $testClass = $shortName . 'Test';
        $testDirPath = base_path('tests/' . $suite . $dirPath);
        $testFilePath = $testDirPath . '/' . $testClass . '.php';

        if (file_exists($testFilePath) && !$this->option('force')) {
            $this->error(
                'Test already exists: ' . Str::after($testFilePath, base_path() . '/') . ' (use --force to overwrite)'
            );

            return 1;
        }

        if ($suite === 'Integration' && Str::contains(Str::lower($fqcn), ['mssql', 'bigquery'])) {
            $this->warn(
                'Heads-up: this looks like an MSSQL/BigQuery source. Per tests.md we never test against the real connection — consider generating a Unit test for an extracted DataMapper instead.'
            );
        }

        $content = $suite === 'Unit'
            ? $this->buildUnitTest($fqcn, $shortName, $testNamespace, $testClass)
            : $this->buildBoundaryTest($fqcn, $shortName, $testNamespace, $testClass);

        if (!is_dir($testDirPath)) {
            mkdir($testDirPath, 0755, true);
        }

        file_put_contents($testFilePath, $content);

        $this->info('Created ' . Str::after($testFilePath, base_path() . '/'));

        return 0;
    }

    private function resolveSuite(): ?string
    {
        $integration = $this->option('integration');
        $feature = $this->option('feature');
        $bare = $this->option('bare');

        if ($integration && $feature) {
            $this->error('Pass either --integration or --feature, not both.');

            return null;
        }

        if ($bare && ($integration || $feature)) {
            $this->error('--bare only applies to Unit tests.');

            return null;
        }

        return $feature ? 'Feature' : ($integration ? 'Integration' : 'Unit');
    }

    private function buildUnitTest(string $fqcn, string $shortName, string $testNamespace, string $testClass): string
    {
        $baseClass = $this->option('bare') ? 'UnitTestCase' : 'TestCase';

        $imports = ["use {$fqcn};"];
        $properties = [];
        $setupLines = [];
        $ctorArgs = [];

        if (class_exists($fqcn) || interface_exists($fqcn)) {
            $constructor = (new ReflectionClass($fqcn))->getConstructor();

            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $param) {
                    [$import, $property, $setup, $arg] = $this->describeParameter($param);

                    if ($import !== null) {
                        $imports[] = $import;
                    }

                    $properties[] = $property;
                    $setupLines[] = $setup;
                    $ctorArgs[] = $arg;
                }
            }
        } else {
            $this->warn("Could not load {$fqcn} via reflection — generating an empty skeleton.");
        }

        $imports[] = 'use Mockery;';
        $imports[] = 'use Mockery\\Adapter\\Phpunit\\MockeryPHPUnitIntegration;';
        $imports[] = "use Tests\\{$baseClass};";
        $imports = array_unique($imports);
        sort($imports);

        $importBlock = implode("\n", $imports);
        $propertiesBlock = $properties === [] ? '' : implode("\n", array_map(fn($p) => "    {$p}", $properties)) . "\n";
        $setupBlock = $setupLines === [] ? '' : implode("\n", array_map(fn($s) => "        {$s}", $setupLines)) . "\n";
        $ctorArgsBlock = $ctorArgs === [] ? '' : implode(
                "\n",
                array_map(fn($a) => "            {$a},", $ctorArgs)
            ) . "\n";

        return <<<PHP
        <?php

        namespace {$testNamespace};

        {$importBlock}

        /**
         * @coversDefaultClass \\{$fqcn}
         */
        class {$testClass} extends {$baseClass}
        {
            use MockeryPHPUnitIntegration;

        {$propertiesBlock}
            protected function setUp(): void
            {
                parent::setUp();

        {$setupBlock}    }

            protected function makeSut(): {$shortName}
            {
                return new {$shortName}(
        {$ctorArgsBlock}        );
            }

            /**
             * @test
             * @covers ::TODO_methodUnderTest
             */
            public function it_should_do_something(): void
            {
                \$sut = \$this->makeSut();

                \$this->markTestIncomplete('TODO: replace with a real assertion');
            }
        }

        PHP;
    }

    /**
     * @return array{0: string|null, 1: string, 2: string, 3: string}
     */
    private function describeParameter(ReflectionParameter $param): array
    {
        $name = $param->getName();
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = ltrim($type->getName(), '\\');
            $shortType = class_basename($typeName);
            $isFinal = (class_exists($typeName) || interface_exists($typeName))
                && (new ReflectionClass($typeName))->isFinal();

            if ($isFinal) {
                return [
                    "use {$typeName};",
                    "protected \${$name};",
                    "// TODO: {$shortType} is final and cannot be mocked with Mockery — inject via an interface or refactor.",
                    "\$this->{$name} /* final, see TODO above */",
                ];
            }

            return [
                "use {$typeName};",
                "protected \${$name};",
                "\$this->{$name} = Mockery::mock({$shortType}::class);",
                "\$this->{$name}",
            ];
        }

        $default = $param->isDefaultValueAvailable() ? var_export($param->getDefaultValue(), true) : 'null';

        return [null, "protected \${$name};", "\$this->{$name} = {$default};", "\$this->{$name}"];
    }

    private function buildBoundaryTest(
        string $fqcn,
        string $shortName,
        string $testNamespace,
        string $testClass
    ): string {
        return <<<PHP
        <?php

        namespace {$testNamespace};

        use {$fqcn};
        use Illuminate\Foundation\Testing\DatabaseTransactions;
        use Tests\TestCase;

        /**
         * @coversDefaultClass \\{$fqcn}
         */
        class {$testClass} extends TestCase
        {
            use DatabaseTransactions;

            /**
             * @test
             * @covers ::TODO_methodUnderTest
             */
            public function it_should_do_something(): void
            {
                \$sut = \$this->app->make({$shortName}::class);

                \$this->markTestIncomplete('TODO: replace with a real assertion against aoc_test');
            }
        }

        PHP;
    }
}
