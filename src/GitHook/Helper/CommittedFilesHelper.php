<?php

/**
 * Copyright © 2017-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace GitHook\Helper;

trait CommittedFilesHelper
{
    /**
     * @return array
     */
    public function getCommittedFiles()
    {
        $output = [];
        $check = 0;

        exec('git rev-parse --verify HEAD 2> /dev/null', $output, $check);

        $against = '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
        if ($check === 0) {
            $against = 'HEAD';
        }

        exec('git diff-index --cached --name-status ' . $against . ' | egrep \'^(A|M)\' | awk \'{print $2;}\'', $committedFiles);

        $prepareFilePathCallback = function ($file) {
            return '.' . PATH_PREFIX . $file;
        };

        $committedFiles = array_map($prepareFilePathCallback, $committedFiles);

        $filterFilesCallback = function ($file) {
            return is_file(PROJECT_ROOT . DIRECTORY_SEPARATOR . $file);
        };

        $committedFiles = array_filter($committedFiles, $filterFilesCallback);

        return $committedFiles;
    }

    /**
     * @param array $excludedPaths
     *
     * @return array
     */
    public function getFilteredCommittedFiles(array $excludedPaths): array
    {
        $committedFiles = $this->getCommittedFiles();
        $excludedFiles = [];

        foreach ($committedFiles as $key => $committedFile) {
            if ($this->isPathExcluded($committedFile, $excludedPaths)) {
                $excludedFiles[] = $committedFiles[$key];
                unset($committedFiles[$key]);
            }
        }

        return [$committedFiles, $excludedFiles];
    }

    /**
     * @param string $filePath
     * @param array $excludedPaths
     *
     * @return bool
     */
    protected function isPathExcluded(string $filePath, array $excludedPaths): bool
    {
        $fileRealPath = realpath($filePath);
        $fileDir = dirname($fileRealPath);

        foreach ($excludedPaths as $excludedPath) {
            if (realpath($excludedPath) === $fileRealPath) {
                return true;
            }

            if (realpath($excludedPath) === $fileDir) {
                return true;
            }
        }

        return false;
    }
}
