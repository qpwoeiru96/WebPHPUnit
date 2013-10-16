<?php
$buildDir = __DIR__ . '/WebPHPUnit';
$targetFile = __DIR__ . '/build/WebPHPUnit.phar';
$phar = new Phar($targetFile, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    'WebPHPUnit.phar');
$phar->startBuffering();
//$phar = $phar->convertToExecutable(Phar::ZIP);
$phar->setStub($phar->createDefaultStub('WebPHPUnit.php'));
$phar->buildFromDirectory($buildDir);
$phar->compressFiles( Phar::GZ ); // 以 GZ 格式壓縮
$phar->stopBuffering();
