<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\FrontendSkeleton;

use Granam\Strict\Object\StrictObject;

class WebVersionSwitcher extends StrictObject
{

    /** @var WebVersions */
    private $webVersions;
    /** @var string */
    private $documentRoot;
    /** @var string */
    private $dirForVersions;

    public function __construct(WebVersions $webVersions, string $documentRoot, string $dirForVersions)
    {
        $this->webVersions = $webVersions;
        $this->documentRoot = $documentRoot;
        $this->dirForVersions = $dirForVersions;
    }

    /**
     * @param string $toVersion
     * @return string
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotInstallLibraries
     */
    public function getVersionIndexFile(string $toVersion): string
    {
        $this->ensureVersionExists($toVersion);

        return $this->getVersionDocumentRoot($toVersion) . '/index.php';
    }

    public function getVersionDocumentRoot(string $version): string
    {
        if ($version === 'master') {
            return $this->documentRoot; // main version to use
        }

        return $this->dirForVersions . '/' . $version;
    }

    /**
     * @param string $toVersion
     * @return bool
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\UnknownVersionToSwitchInto
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotLocallyCloneGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotUpdateGitVersion
     * @throws \DrdPlus\FrontendSkeleton\Exceptions\CanNotInstallLibraries
     */
    protected function ensureVersionExists(string $toVersion): bool
    {
        if ($toVersion === $this->webVersions->getCurrentVersion()) {
            return true; // we are done
        }
        if (!$this->webVersions->hasVersion($toVersion)) {
            throw new Exceptions\UnknownVersionToSwitchInto("Required version {$toVersion} does not exist");
        }
        $toVersionDir = $this->dirForVersions . '/' . $toVersion;
        if (!\file_exists($toVersionDir)) {
            $command = 'git clone --branch ' . \escapeshellarg($toVersion) . ' . ' . \escapeshellarg($toVersionDir) . ' 2>&1'
                . ' && export COMPOSER_HOME=. && composer --working-dir=' . \escapeshellarg($toVersionDir) . ' install 2>&1';
            \exec($command, $rows, $returnCode);
            if ($returnCode !== 0) {
                throw new Exceptions\CanNotLocallyCloneGitVersion(
                    "Can not git clone required version '{$toVersion}' by command '{$command}'"
                    . ", got return code '{$returnCode}' and output\n"
                    . \implode("\n", $rows)
                );
            }
        } else {
            $command = 'cd ' . \escapeshellarg($toVersionDir) . ' 2>&1 && git reset HEAD --hard 2>&1 && git pull --ff-only 2>&1';
            $rows = []; // resetting rows as they may NOT be changed on failure
            \exec($command, $rows, $returnCode);
            if ($returnCode !== 0) {
                throw new Exceptions\CanNotUpdateGitVersion(
                    "Can not update required version '{$toVersion}' by command '{$command}'"
                    . ", got return code '{$returnCode}' and output\n"
                    . \implode("\n", $rows)
                );
            }
            if (\end($rows) !== 'Already up to date.') {
                $command = 'cd ' . \escapeshellarg($toVersionDir) . ' 2>&1 && export COMPOSER_HOME=. && composer install 2>&1';
                $rows = []; // resetting rows as they may NOT be changed on failure
                \exec($command, $rows, $returnCode);
                if ($returnCode !== 0) {
                    throw new Exceptions\CanNotInstallLibraries(
                        "Can not install libraries via Composer by command '{$command}'"
                        . ", got return code '{$returnCode}' and output:"
                        . ($rows
                            ? "\n" . \implode("\n", $rows)
                            : " ''"
                        )
                    );
                }
            }
        }

        return true;
    }
}