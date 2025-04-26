<?php

namespace Bojaghi\ImposterAutoloadPatcher;

use InvalidArgumentException;

class AutoloadPatcher
{
    private string $vendor;

    private string $prefix;

    /**
     * Regex converted from extra.imposterAutoloadPatcher.targets
     *
     * @var string
     */
    private string $targets;

    public function __construct(string $composerPath)
    {
        $composer = json_decode(file_get_contents($composerPath) ?: '', true) ?: [];

        $this->vendor = $composer['config']['vendor-dir'] ?? 'vendor';

        $this->prefix = addslashes(
            self::trimNamespace($composer['extra']['imposterAutoloadPatcher']['prefix'] ?? '')
        );
        if (!$this->prefix) {
            throw new InvalidArgumentException('Prefix is required.');
        }

        $this->targets = implode(
            '|',
            array_map(
                fn($t) => addslashes(self::trimNamespace($t)),
                $composer['extra']['imposterAutoloadPatcher']['targets'] ?? []
            )
        );
    }

    public function patch(): void
    {
        $file = $this->vendor . '/composer/autoload_static.php';

        $content = file_get_contents($file);
        if (!$content || !$this->targets) {
            return;
        }

        // patch `public static $prefixLengthsPsr4 = array(...)` part
        $content = $this->patchLength($content);

        // patch `public static $prefixDirsPsr4 = array(...)` part
        $content = $this->patchDirs($content);

        file_put_contents($file, $content);
    }

    /**
     * Extracts and slices a given string based on a regular expression pattern.
     *
     * @param string $regex   The regular expression pattern to match within the content.
     * @param string $content The string content to be processed and sliced.
     *
     * @return array{
     *     before: string,
     *     middle: string,
     *     after: string,
     * }|false Returns an associative array with keys 'before', 'middle', and 'after' if the match is successful;
     *         otherwise returns false if no match is found.
     */
    private function sliceText(string $regex, string $content): array|false
    {
        if (!preg_match($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $before = substr($content, 0, (int)$matches[0][1]);
        $after  = substr($content, strlen($matches[0][0]) + (int)$matches[0][1]);

        return [
            'before' => $before,
            'middle' => $matches[0][0],
            'after'  => $after,
        ];
    }

    private function patchLength(string $input): string
    {
        $slice = $this->sliceText(
            '/^\s*public\s+static\s+\$prefixLengthsPsr4\s*=\s*array\s*\(.+?\)\s*;$/ms',
            $input
        );

        $prefixLen = strlen(stripslashes($this->prefix));
        $replaced  = preg_replace_callback(
            ";'((?:$this->targets)[^ ]+)'\s*=>\s*(\d+)\s*,;",
            fn($matches) => sprintf("'%s%s' => %d,", $this->prefix, $matches[1], $prefixLen + (int)$matches[2]),
            $slice['middle'],
        );

        return $slice['before'] . $replaced . $slice['after'];
    }

    private function patchDirs(string $input): string
    {
        $slice = $this->sliceText(
            '/^\s*public\s+static\s+\$prefixDirsPsr4\s*=\s*array\s*\(.+?\)\s*;$/ms',
            $input,
        );

        $replaced = preg_replace_callback(
            ";'((?:$this->targets)[^ ]+)'\s*=>\s+;",
            fn($matches) => "'$this->prefix$matches[1]' => ",
            $slice['middle'],
        );

        return $slice['before'] . $replaced . $slice['after'];
    }

    public static function trimNamespace(string $namespace): string
    {
        $trimmed = trim($namespace, '\\');

        return $trimmed ? ($trimmed . '\\') : '';
    }
}
