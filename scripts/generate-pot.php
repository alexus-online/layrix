#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

$targetDir = $root . '/languages';
if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Could not create languages directory.\n");
    exit(1);
}

$targetFile = $targetDir . '/ecf-framework.pot';
$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo) {
        continue;
    }

    $path = $file->getPathname();
    if ($file->getExtension() !== 'php') {
        continue;
    }
    if (strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }
    if (strpos($path, DIRECTORY_SEPARATOR . '.claude' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }
    if (strpos($path, DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }

    $files[] = $path;
}

sort($files);

$messages = [];

$addMessage = static function (string $message, string $reference) use (&$messages): void {
    $message = trim($message);
    if ($message === '') {
        return;
    }
    if (!isset($messages[$message])) {
        $messages[$message] = [];
    }
    if (!in_array($reference, $messages[$message], true)) {
        $messages[$message][] = $reference;
    }
};

$decodeLiteral = static function (string $literal): string {
    $quote = $literal[0] ?? "'";
    $body = substr($literal, 1, -1);
    if ($quote === "'") {
        return str_replace(["\\\\", "\\'"], ["\\", "'"], $body);
    }
    return stripcslashes($body);
};

foreach ($files as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        continue;
    }

    $relative = ltrim(str_replace($root, '', $file), DIRECTORY_SEPARATOR);
    $lines = preg_split("/\r\n|\n|\r/", $contents);

    foreach ($lines as $index => $line) {
        $lineNumber = $index + 1;
        $reference = $relative . ':' . $lineNumber;

        if (preg_match_all('/->t\(\s*(\'(?:\\\\.|[^\'])*\'|"(?:\\\\.|[^"])*")\s*,\s*(\'(?:\\\\.|[^\'])*\'|"(?:\\\\.|[^"])*")/u', $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $addMessage($decodeLiteral($match[1]), $reference);
            }
        }

        if (preg_match_all('/\b(?:__|_e|esc_html__|esc_attr__)\(\s*(\'(?:\\\\.|[^\'])*\'|"(?:\\\\.|[^"])*")\s*,\s*(\'ecf-framework\'|"ecf-framework")/u', $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $addMessage($decodeLiteral($match[1]), $reference);
            }
        }
    }
}

ksort($messages);

$pot = [];
$pot[] = 'msgid ""';
$pot[] = 'msgstr ""';
$pot[] = '"Project-Id-Version: Layrix\\n"';
$pot[] = '"Report-Msgid-Bugs-To: \\n"';
$pot[] = '"POT-Creation-Date: ' . gmdate('Y-m-d H:i+0000') . '\\n"';
$pot[] = '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"';
$pot[] = '"Last-Translator: \\n"';
$pot[] = '"Language-Team: \\n"';
$pot[] = '"Language: \\n"';
$pot[] = '"MIME-Version: 1.0\\n"';
$pot[] = '"Content-Type: text/plain; charset=UTF-8\\n"';
$pot[] = '"Content-Transfer-Encoding: 8bit\\n"';
$pot[] = '"X-Domain: ecf-framework\\n"';
$pot[] = '';

foreach ($messages as $message => $references) {
    sort($references);
    foreach ($references as $reference) {
        $pot[] = '#: ' . $reference;
    }
    $pot[] = 'msgid "' . addcslashes($message, "\\\"\n\r\t") . '"';
    $pot[] = 'msgstr ""';
    $pot[] = '';
}

file_put_contents($targetFile, implode("\n", $pot));
fwrite(STDOUT, "Generated {$targetFile}\n");
