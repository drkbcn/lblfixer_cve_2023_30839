<?php

/**
 * 2023 LabelGrup Networks SL
 *
 * NOTICE OF LICENSE
 *
 * READ ATTACHED LICENSE.TXT
 *
 *  @author    Manel Alonso <malonso@labelgrup.com>, <admin@ethicalhackers.es>
 *  @copyright 2023 LabelGrup Networks SL
 *  @license   LICENSE.TXT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Lblfixer_cve_2023_30839 extends Module
{
    public function __construct()
    {
        $this->name = 'lblfixer_cve_2023_30839';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'LabelGrup Networks SL, Manel Alonso';
        $this->need_instance = 0;
        $this->displayName = $this->l('LabelGrup.com FIX CVE-2023-30839 (for PrestaShop <1.7.8.9)');
        $this->description = $this->l('Fixes CVE-2023-30839 vulnerability.');
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => '1.7.8.8');
        $this->confirmUninstall = $this->l('Your shop will be vulnerable to CVE-2023-30839.') .
            $this->l('Are you sure you want to uninstall this addon?');

        parent::__construct();
    }

    public function install()
    {
        $this->patchCVE();
        return parent::install();
    }

    public function uninstall()
    {
        $this->unpatchCVE();
        return parent::uninstall();
    }

    /**
     * Get the path to the file to patch
     * @return string
     */
    private function getFilePath()
    {
        return _PS_CLASS_DIR_ . 'db/';
    }

    /**
     * Apply patch for CVE-2023-30839
     * @return bool
     */
    private function patchCVE()
    {
        if ($this->detectAlreadyPatched()) {
            return true;
        }

        return $this->patchFiles();
    }

    /**
     * Remove patch for CVE-2023-30839
     * @return bool
     */
    private function unpatchCVE()
    {
        return $this->unpatchFiles();
    }

    /**
     * Detect if the patch is already applied
     * @return bool
     */
    private function detectAlreadyPatched()
    {
        // For each file to patch, check if the patch is already applied
        $files_to_patch = $this->getFilesToPatch();
        $base_path = $this->getFilePath();

        foreach ($files_to_patch as $file_to_patch) {
            $path = $base_path . $file_to_patch;
            if ($this->detectAlreadyPatchedFile($path, 'CVE-2023-30839')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if the patch is already applied for a file
     * @return bool
     */
    private function detectAlreadyPatchedFile($file, $pattern)
    {
        if (file_exists($file)) {
            $content = Tools::file_get_contents($file);
            if (strpos($content, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the list of files to patch
     * @return array
     */
    private function getPatchFiles()
    {
        return glob(dirname(__FILE__) . '/patches/*.patch');
    }

    /**
     * Get the list of files to patch
     * @return array
     */
    private function getFilesToPatch()
    {
        $file_pathes = $this->getPatchFiles();
        foreach ($file_pathes as $file_path) {
            $file_name = basename($file_path);
            $file_name = str_replace('.patch', '', $file_name);
            $files[] = $file_name;
        }
        return $files;
    }

    /**
     * Parse a patch file
     * @param string $file_path Path to the patch file
     * @return array
     */
    private function parsePatchFile($file_path)
    {
        $patches = explode('¤', Tools::file_get_contents($file_path));
        return [$patches];
    }

    /**
     * Patches all files
     * @return bool
     */
    private function patchFiles()
    {
        $files_to_patch = $this->getFilesToPatch();
        $base_path = $this->getFilePath();

        foreach ($files_to_patch as $file_to_patch) {
            $path = $base_path . $file_to_patch;
            $patch_file = __DIR__ . '/patches/' . $file_to_patch . '.patch';
            if (!$this->patchFile($path, $patch_file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Patches a file
     * @param string $path Path to the file to patch
     * @param string $patch_file Path to the patch file
     * @return bool
     */
    private function patchFile($path, $patch_file)
    {
        if (!$this->backupFile($path)) {
            return false;
        }

        $content = Tools::file_get_contents($path);
        $patches_for_file = $this->parsePatchFile($patch_file);
        foreach ($patches_for_file as $patch) {
            $original = $patch[0];
            $replacement = $patch[1];
            $content = str_replace($original, $replacement, $content);
        }

        if (!@file_put_contents($path, $content)) {
            return false;
        }

        return true;
    }

    /**
     * Backup the original file
     * @param string $path Path to the file to patch
     * @return bool
     */
    private function backupFile($path)
    {
        $filename = basename($path);
        if (!@copy($path, dirname(__FILE__) . '/backup/' . $filename)) {
            return false;
        }

        return true;
    }

    /**
     * Restore the original files
     * @return bool
     */
    private function unpatchFiles()
    {
        $files_to_patch = $this->getFilesToPatch();
        $base_path = $this->getFilePath();

        foreach ($files_to_patch as $file_to_patch) {
            $path = $base_path . $file_to_patch;
            $backup_file = __DIR__ . '/backup/' . $file_to_patch;
            if (@copy($backup_file, $path)) {
                @unlink($backup_file);
            }
        }

        return true;
    }
}
