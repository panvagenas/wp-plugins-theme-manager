<?php

/*
 * Copyright (C) 2014 Panagiotis Vagenas <pan.vagenas@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

add_action('vplugin_register_theme', array('VPluginThemeFactory', 'registerTheme'));
add_action('vplugin_register_all_themes_in_folder', array('VPluginThemeFactory', 'registerAllThemesInFolder'));

/**
 * VPluginThemeFactory.php
 *
 * @package   @todo
 * @author    Panagiotis Vagenas <pan.vagenas@gmail.com>
 * @link      @todo
 * @copyright 2014 Panagiotis Vagenas <pan.vagenas@gmail.com>
 */

/**
 * Description of VPluginThemeFactory
 * 
 * @package @todo
 * @author Panagiotis Vagenas <pan.vagenas@gmail.com>
 */
class VPluginThemeFactory {
    /**
     * Array of registered themes
     * @var array
     */
    public static $registeredThemes = array();

    public static function registerTheme(VPluginTheme $theme) {
        self::$registeredThemes[$theme->getUniqueID()] = $theme;
    }
    
    public static function registerThemeInPath($path, $name = null){
        /**
         * Check if we allready have this theme by name
         */
        if(!empty($name) && is_string($name)){
            $found = self::getThemeByName($name);
            if($found !== null){
                return $found;
            }
        }
        /**
         * If not search it, register it and return it
         */
        // If path is to file
        if(is_file($path)){
            require_once $path;
            $classesInFile = VPluginFileHelper::file_get_php_classes($path);
            if(is_array($classesInFile) && !empty($classesInFile)){
                foreach ($classesInFile as $key => $value) {
                    if(class_exists($value)){
                        $theme = new $value;
                        if($theme instanceof VPluginTheme){
                            self::registerTheme($theme);
                            return $theme;
                        }
                    }
                }
            }
        } else {
            $files = VPluginFileHelper::filesToArray($path);
            foreach ($files as $key => $value) {
                $absPathToFile = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$value;
                $theme = self::registerThemeInPath($absPathToFile, $name);
                if($theme instanceof VPluginTheme && (empty($name) || $theme->getName() == $name)){
                    self::registerTheme($theme);
                    return $theme;
                }
            }
        }
        return null;
    }

    /**
     * Get all themes of a given type
     * @param string $type
     * @return \VPluginTheme
     */
    public static function getAllOfType($type) {
        $out = array();
        foreach (self::$registeredThemes as $key => $value) {
            if($value instanceof VPluginTheme && $value->getType() == $type){
                array_push($out, $value);
            }
        }
        return $value;
    }
    
    public static function getThemesNames($type = false) {
        $out = array();
        $themes = $type ? self::getAllOfType($type) : self::$registeredThemes;
        foreach ($themes as $key => $value) {
            $out[$value->getUniqueID()] = $value->getName();
        }
        return $out;
    }
    
    public static function getThemeByName($name) {
        if(is_string($name)){
            foreach (self::$registeredThemes as $key => $value) {
                if($value->getName() == $name){
                    return $value;
                }
            }
        }
        return null;
    }

    public static function registerAllThemesInFolder($path) {
        
    }
    
    public static function getThemeByUniqueID($id) {
        return isset(self::$registeredThemes[$id]) ? self::$registeredThemes[$id] : null;
    }

    public static function getRegisteredThemes($type = false, $names = false) {
        if($names){
            return self::getThemesNames($type);
        }
        return $type ? self::getAllOfType($type) : self::$registeredThemes;
    }

    private static function isValidTheme(Object $object) {
        return $object instanceof VPluginTheme;
    }

    private static function getClassesOfFile($filePath) {
        if (is_string($filePath) && !is_file($filePath)) {
            return VPluginFileHelper::file_get_php_classes($filePath);
        }
        return array();
    }

}

/**
 * File helper class
 *
 * @author    Panagiotis Vagenas <pan.vagenas@gmail.com>
 */
class VPluginFileHelper {

    public static function file_get_php_classes($filepath) {
        $php_code = file_get_contents($filepath);
        $classes = get_php_classes($php_code);
        return $classes;
    }

    public static function get_php_classes($php_code) {
        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }
        return $classes;
    }

    /**
     * Scans recursivly a folder and returns its contents as assoc array
     *
     * @param string $path
     * @return array
     * @author Panagiotis Vagenas <pan.vagenas@gmail.com>
     * @since 1.0.0
     */
    public static function dirToArrayRecursive($path) {
        $contents = array();
        // Foreach node in $path
        foreach (scandir($path) as $node) {
            // Skip link to current and parent folder
            if ($node == '.' || $node == '..') {
                continue;
            }
            // Check if it's a node or a folder
            if (is_dir($path . DIRECTORY_SEPARATOR . $node)) {
                // Add directory recursively, be sure to pass a valid path
                // to the function, not just the folder's name
                $contents [$node] = self::dirToArrayRecursive($path . DIRECTORY_SEPARATOR . $node);
            } else {
                // Add node, the keys will be updated automatically
                $contents [] = $node;
            }
        }
        // done
        return $contents;
    }

    /**
     * Scans a folder and returns its contents as array
     *
     * @param string $path
     * @return array
     * @author Panagiotis Vagenas <pan.vagenas@gmail.com>
     * @since 1.0.0
     */
    public static function dirToArray($path) {
        $contents = array();
        // Foreach node in $path
        foreach (scandir($path) as $node) {
            // Skip link to current and parent folder
            if ($node == '.' || $node == '..')
                continue;
            // Check if it's a node or a folder
            if (is_dir($path . DIRECTORY_SEPARATOR . $node)) {
                $contents [] = $node;
            }
        }
        // done
        return $contents;
    }

    /**
     * Returns all files of a folder as an array
     *
     * @param string $path
     * @return array
     * @author Panagiotis Vagenas <pan.vagenas@gmail.com>
     * @since 1.0.0
     */
    public static function filesToArray($path) {
        if (empty($path)) {
            return array();
        }
        $contents = array();
        // Foreach node in $path
        foreach (scandir($path) as $node) {
            // Skip link to current and parent folder
            if ($node == '.' || $node == '..') {
                continue;
            }
            // Check if it's a node or a folder
            if (is_file($path . DIRECTORY_SEPARATOR . $node)) {
                $contents [] = $node;
            }
        }
        // done
        return $contents;
    }

}
