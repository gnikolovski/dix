<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ConfigCommandTest extends TestCase
{
    private static $testExportDirPath;
    private static $exportDirPath;
    private static $testAWSPath;
    private static $AWSPath;
    private static $configFilePath;

    public static function setUpBeforeClass()
    {
        self::$testExportDirPath = $_SERVER['HOME'] . '/backup/dbtest';
        self::$exportDirPath = $_SERVER['HOME'] . '/backup/db';
        self::$testAWSPath = 's3://backup/dbtest';
        self::$AWSPath = 's3://backup/db';
        self::$configFilePath = $_SERVER['HOME'] . '/dix/config.yml';
    }

    /**
     * Test config command output.
     */
    public function testConfigOutput()
    {
        $result = shell_exec('dix config');
        $this->assertContains('Directory', $result);
        $this->assertContains('AWS path', $result);
        $this->assertContains('Database', $result);
        $this->assertContains('Username', $result);
        $this->assertContains('Password', $result);

        $result = shell_exec('dix cf');
        $this->assertContains('Directory', $result);
        $this->assertContains('AWS path', $result);
        $this->assertContains('Database', $result);
        $this->assertContains('Username', $result);
        $this->assertContains('Password', $result);
    }

    /**
     * Test setting export directory location.
     */
    public function testSetExportDir()
    {
        // Set export directory.
        if (!file_exists(self::$testExportDirPath)) {
            mkdir(self::$testExportDirPath, 0777, true);
        }
        $result = shell_exec('dix config --dir=' . self::$testExportDirPath);
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertEquals(self::$testExportDirPath, $config['directory']);
        $this->assertContains('Success', $result);
        $this->assertContains('Configuration has been successfully updated', $result);

        // Update export directory.
        if (!file_exists(self::$exportDirPath)) {
            mkdir(self::$exportDirPath, 0777, true);
        }
        $result = shell_exec('dix config --dir=' . self::$exportDirPath);
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertEquals(self::$exportDirPath, $config['directory']);
        $this->assertContains('Success', $result);
        $this->assertContains('Configuration has been successfully updated', $result);
    }

    /**
     * Test setting Amazon Web Service path.
     */
    public function testSetAWSPath()
    {
        // Set path.
        $result = shell_exec('dix config --aws-path=' . self::$testAWSPath);
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertEquals(self::$testAWSPath, $config['aws_path']);
        $this->assertContains('Success', $result);
        $this->assertContains('Configuration has been successfully updated', $result);

        // Update path.
        $result = shell_exec('dix config --aws-path=' . self::$AWSPath);
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertEquals(self::$AWSPath, $config['aws_path']);
        $this->assertContains('Success', $result);
        $this->assertContains('Configuration has been successfully updated', $result);
    }

    /**
     * Test setting and removing database credentials.
     */
    public function testSetDBCredentials()
    {
        // Set database credentials.
        shell_exec('dix config --dbname=DBTEST --user=USERTEST --pass=PASSTEST');
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertArrayHasKey('DBTEST', $config['databases']);
        $this->assertArrayHasKey('username', $config['databases']['DBTEST']);
        $this->assertArrayHasKey('password', $config['databases']['DBTEST']);
        $this->assertEquals('USERTEST', $config['databases']['DBTEST']['username']);
        $this->assertEquals('PASSTEST', $config['databases']['DBTEST']['password']);

        // Remove database credentials.
        shell_exec('dix config --dbname=DBTEST --user --pass');
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertArrayNotHasKey('DBTEST', $config['databases']);

        // Remove database credentials - short.
        shell_exec('dix config --dbname=DBTEST --user=USERTEST --pass=PASSTEST');
        shell_exec('dix config --dbname=DBTEST');
        $config = Yaml::parse(file_get_contents(self::$configFilePath));
        $this->assertArrayNotHasKey('DBTEST', $config['databases']);
    }
}
