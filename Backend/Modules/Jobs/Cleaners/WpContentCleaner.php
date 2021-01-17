<?php

namespace WPStaging\Backend\Modules\Jobs\Cleaners;

use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Core\Utils\Logger;

/**
 * This class is used to delete all uploads, themes and plugins
 * Currently it is used during push process
 * It will delete uploads, themes and plugins according to the options user selected.
 */
class WpContentCleaner
{
    /**
     * @var array
     */
    private $cleanerLogs = [];

    /**
     * @var Job
     */
    private $job;

    /**
     * @param Job $job
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Return logs of this cleaning process
     * @return array
     */
    public function getLogs()
    {
        return $this->cleanerLogs;
    }

    /**
     * Remove Plugins/Themes/Uploads according to option selected
     * $directory param used in this method is mainly for mocking purpose but,
     * can also be used to give path of staging site
     * @param string $directory Root directory of target WordPress Installation
     * @return bool
     * 
     * @todo update for clone when clone is network after merging that PR
     */
    public function tryCleanWpContent($directory)
    {
        $options = $this->job->getOptions();

        if ($options->contentCleaned === 'finished' || $options->contentCleaned === 'skipped') {
            return true;
        }

        if (!is_dir($directory)) {
            $this->cleanerLogs[] = [
                "msg" => sprintf(__("Files: Error - No such directory exists: %s.", "wp-staging"), $directory),
                "type" => Logger::TYPE_ERROR
            ];
            return false;
        }
        
        $wpDirectories = new WpDefaultDirectories();
        $directory = trailingslashit($directory);
        $paths = [];
        if ($options->deleteUploadsFolder && !$options->backupUploadsFolder && $options->contentCleaned = 'pending') {
            $paths[] = trailingslashit($directory . $wpDirectories->getRelativeUploadPath());
        }

        if ($options->removeUninstalledPluginsThemes) {
            $paths[] = trailingslashit($directory . $wpDirectories->getRelativeThemePath());
            $paths[] = trailingslashit($directory . $wpDirectories->getRelativePluginPath());
        }

        if (count($paths) === 0) {
            $options->contentCleaned = 'skipped';
            $this->job->saveOptions($options);
            if (!$options->deleteUploadsFolder) {
                $this->cleanerLogs[] = [
                    "msg" => __("Files: Skipped cleaning Uploads, Plugins and Themes directories!", "wp-staging"),
                    "type" => Logger::TYPE_INFO
                ];
            } else {
                $this->cleanerLogs[] = [
                    "msg" => __("Files: Skipped cleaning Plugins and Themes directories!", "wp-staging"),
                    "type" => Logger::TYPE_INFO
                ];
            }

            return true;
        }

        if ($options->contentCleaned === 'pending') {
            // return if any of the given paths is not a dir
            // only check if this process is pending as during cleaning those directories may be deleted
            foreach($paths as $path) {
                if (!is_dir($path)) {
                    $this->cleanerLogs[] = [
                        "msg" => sprintf(__("Files: Error - No such directory exists: %s.", "wp-staging"), $path),
                        "type" => Logger::TYPE_ERROR
                    ];
                    return false;
                }
            }

            if (!$options->deleteUploadsFolder) {
                $this->cleanerLogs[] = [
                    "msg" => __("Files: Skipped cleaning Uploads Dir!", "wp-staging"),
                    "type" => Logger::TYPE_INFO
                ];
            }

            $this->cleanerLogs[] = [
                "msg" => __("Files: Starting cleaning dirs!", "wp-staging"),
                "type" => Logger::TYPE_INFO
            ];

            $options->contentCleaned = 'cleaning';
            $this->job->saveOptions($options);
        }

        $excludePaths = [
            "wp-staging",
            "wp-staging-1",
            "wp-staging-pro",
            "wp-staging-pro-1",
            "wp-staging-dev",
            'cache',
            'wps-hide-login',
            'wp-staging-hooks',
        ];
        $fs = (new Filesystem())
            ->setShouldStop([$this->job, 'isOverThreshold'])
            ->setExcludePaths($excludePaths)
            ->setRecursive();
        try {
            if (!$fs->deletePaths($paths)) {
                return false;
            }
        } catch (\RuntimeException $ex) {
            $this->cleanerLogs[] = [
                "msg" => sprintf(__("Files: Error - %s. Content cleaning.", "wp-staging"), $ex->getMessage()),
                "type" => Logger::TYPE_ERROR
            ];
            return false;
        }

        $options->contentCleaned = 'finished';
        $this->job->saveOptions($options);
        if (!$options->removeUninstalledPluginsThemes) {
            $this->cleanerLogs[] = [
                "msg" => __("Files: Skipped cleaning Plugins and Themes directories!", "wp-staging"),
                "type" => Logger::TYPE_INFO
            ];
        }

        $this->cleanerLogs[] = [
            "msg" => __("Files: Finished cleaning!", "wp-staging"),
            "type" => Logger::TYPE_INFO
        ];

        return true;
    }
}