<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Bundle\DataHubBundle\Controller\ConfigController;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Logger;

class Installer extends MigrationInstaller
{

    public function getMigrationVersion(): string
    {
        return '20190614135736';
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        $db = Db::get();
        $check = $db->fetchOne("SELECT `key` FROM users_permission_definitions where `key` = ?", [ConfigController::CONFIG_NAME]);

        return (bool)$check;
    }

    /**
     * {@inheritdoc}
     */
    public function migrateInstall(Schema $schema, Version $version)
    {
        // create backend permission
        \Pimcore\Model\User\Permission\Definition::create(ConfigController::CONFIG_NAME);

        try {
            $types = ["asset", "object"];

            $db = Db::get();
            foreach ($types as $type) {
                $db->query("
                    CREATE TABLE IF NOT EXISTS `plugin_datahub_workspaces_" . $type . "` (
                        `cid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
                        `cpath` VARCHAR(765) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
                        `configuration` VARCHAR(50) NOT NULL DEFAULT '0',
                        `create` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        `read` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        `update` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                        `delete` TINYINT(1) UNSIGNED NULL DEFAULT '0',                    
                        PRIMARY KEY (`cid`, `configuration`)                
                        )
                    COLLATE='utf8mb4_general_ci'
                    ENGINE=InnoDB
                    ;                        
                ");
            }
        } catch (\Exception $e) {
            Logger::warn($e);
        }

        return true;
    }

    public function migrateUninstall(Schema $schema, Version $version)
    {
    }
}
