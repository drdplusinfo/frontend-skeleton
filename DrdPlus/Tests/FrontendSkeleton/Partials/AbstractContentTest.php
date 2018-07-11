<?php
declare(strict_types=1);
/** be strict for parameter types, https://www.quora.com/Are-strict_types-in-PHP-7-not-a-bad-idea */

namespace DrdPlus\Tests\FrontendSkeleton\Partials;

use DrdPlus\FrontendSkeleton\Cache;
use DrdPlus\FrontendSkeleton\FrontendController;
use DrdPlus\FrontendSkeleton\HtmlHelper;
use Gt\Dom\HTMLDocument;

abstract class AbstractContentTest extends SkeletonTestCase
{
    private static $contents = [];
    private static $htmlDocuments = [];
    protected $needPassIn = true;
    protected $needPassOut = false;

    protected function setUp(): void
    {
        if (!\defined('DRD_PLUS_INDEX_FILE_NAME_TO_TEST')) {
            self::markTestSkipped("Missing constant 'DRD_PLUS_INDEX_FILE_NAME_TO_TEST'");
        }
    }

    /**
     * @param array $get = []
     * @param array $post = []
     * @param array $cookies = []
     * @return string
     */
    protected function getContent(array $get = [], array $post = [], array $cookies = []): string
    {
        $key = $this->createKey($get, $post, $cookies);
        if ((self::$contents[$key] ?? null) === null) {
            $originalGet = $_GET;
            $originalPost = $_POST;
            $originalCookies = $_COOKIE;
            if ($get) {
                $_GET = \array_merge($_GET, $get);
            }
            if ($post) {
                $_POST = \array_merge($_POST, $post);
            }
            if ($cookies) {
                $_COOKIE = \array_merge($_COOKIE, $cookies);
            }
            if ($this->needPassIn()) {
                $this->passIn();
            } elseif ($this->needPassOut()) {
                $this->passOut();
            }
            /** @noinspection PhpUnusedLocalVariableInspection */
            $latestVersion = 'master';
            \ob_start();
            /** @noinspection PhpIncludeInspection */
            include DRD_PLUS_INDEX_FILE_NAME_TO_TEST;
            self::$contents[$key] = \ob_get_clean();
            $_POST = $originalPost;
            $_GET = $originalGet;
            $_COOKIE = $originalCookies;
            self::assertNotEmpty(
                self::$contents[$key],
                'Nothing has been fetched with GET ' . \var_export($get, true) . ', POST ' . \var_export($post, true)
                . ' and COOKIE ' . \var_export($cookies, true)
                . ' from ' . DRD_PLUS_INDEX_FILE_NAME_TO_TEST
            );
        }

        return self::$contents[$key];
    }

    protected function createKey(array $get, array $post, array $cookies): string
    {
        return \json_encode($get) . '-' . \json_encode($post) . '-' . \json_encode($cookies) . '-' . (int)$this->needPassIn() . (int)$this->needPassOut();
    }

    /**
     * Intended for overwrite if protected content is accessed
     */
    protected function passIn(): bool
    {
        return true;
    }

    protected function needPassIn(): bool
    {
        return $this->needPassIn;
    }

    /**
     * Intended for overwrite if protected content is accessed
     */
    protected function passOut(): bool
    {
        return true;
    }

    protected function needPassOut(): bool
    {
        return $this->needPassOut;
    }

    /**
     * @param array $get
     * @param array $post
     * @param array $cookies
     * @return \DrdPlus\FrontendSkeleton\HtmlDocument
     */
    protected function getHtmlDocument(array $get = [], array $post = [], array $cookies = []): \DrdPlus\FrontendSkeleton\HtmlDocument
    {
        $key = $this->createKey($get, $post, $cookies);
        if (empty(self::$htmlDocuments[$key])) {
            self::$htmlDocuments[$key] = new \DrdPlus\FrontendSkeleton\HtmlDocument($this->getContent($get, $post, $cookies));
        }

        return self::$htmlDocuments[$key];
    }

    protected function isSkeletonChecked(): bool
    {
        $documentRootRealPath = \realpath($this->getDocumentRoot());
        $frontendSkeletonRealPath = \realpath(__DIR__ . '/../../../..');

        return $documentRootRealPath === $frontendSkeletonRealPath;
    }

    protected function getCurrentPageTitle(HTMLDocument $document = null): string
    {
        $head = ($document ?? $this->getHtmlDocument())->head;
        if (!$head) {
            return '';
        }
        $titles = $head->getElementsByTagName('title');
        if ($titles->count() === 0) {
            return '';
        }
        $titles->rewind();

        return $titles->current()->nodeValue;
    }

    protected function getDocumentRoot(): string
    {
        static $documentRoot;
        if ($documentRoot === null) {
            $documentRoot = \dirname(\DRD_PLUS_INDEX_FILE_NAME_TO_TEST);
        }

        return $documentRoot;
    }

    protected function getDirForVersions(): string
    {
        return $this->getDocumentRoot() . '/versions';
    }

    protected function getVendorRoot(): string
    {
        return $this->getDocumentRoot() . '/vendor';
    }

    protected function getWebFilesRoot(): string
    {
        return $this->getDocumentRoot() . '/web';
    }

    protected function getDefinedPageTitle(): string
    {
        return (new FrontendController('Google Foo', $this->createHtmlHelper(), $this->getDocumentRoot()))->getPageTitle();
    }

    /**
     * @param bool|null $inProductionMode
     * @return HtmlHelper|\Mockery\MockInterface
     */
    protected function createHtmlHelper(bool $inProductionMode = null): HtmlHelper
    {
        $htmlHelper = $this->mockery(HtmlHelper::class);
        if ($inProductionMode !== null) {
            $htmlHelper->shouldReceive('isInProduction')
                ->andReturn($inProductionMode);
        }

        return $htmlHelper;
    }

    protected function fetchNonCachedContent(FrontendController $controller = null): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $controller = $controller ?? null;
        $cacheOriginalValue = $_GET[Cache::CACHE] ?? null;
        $_GET[Cache::CACHE] = Cache::DISABLE;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $latestVersion = 'master';
        \ob_start();
        /** @noinspection PhpIncludeInspection */
        include $this->getDocumentRoot() . '/index.php';
        $content = \ob_get_clean();
        $_GET[Cache::CACHE] = $cacheOriginalValue;

        return $content;
    }

    protected function turnToLocalLink(string $link): string
    {
        return \preg_replace('~https?://((?:[[:alnum:]]+\.)*)drdplus\.info~', 'http://$1drdplus.loc', $link); // turn link into local version
    }

    protected function fetchContentFromLink(string $link, bool $withBody, array $post = [], array $cookies = [], array $headers = []): array
    {
        $curl = \curl_init($link);
        \curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($curl, \CURLOPT_CONNECTTIMEOUT, 7);
        if (!$withBody) {
            // to get headers only
            \curl_setopt($curl, \CURLOPT_HEADER, 1);
            \curl_setopt($curl, \CURLOPT_NOBODY, 1);
        }
        \curl_setopt($curl, \CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:58.0) Gecko/20100101 Firefox/58.0'); // to get headers only
        if ($post) {
            \curl_setopt($curl, \CURLOPT_POSTFIELDS, $post);
        }
        if ($cookies) {
            $cookieData = [];
            foreach ($cookies as $name => $value) {
                $cookieData[] = "$name=$value";
            }
            \curl_setopt($curl, \CURLOPT_COOKIE, \implode('; ', $cookieData));
        }
        foreach ($headers as $headerName => $headerValue) {
            \curl_setopt($curl, \CURLOPT_HEADER, "$headerName=$headerValue");
        }
        $content = \curl_exec($curl);
        $responseHttpCode = \curl_getinfo($curl, \CURLINFO_HTTP_CODE);
        $redirectUrl = \curl_getinfo($curl, \CURLINFO_REDIRECT_URL);
        $curlError = \curl_error($curl);
        \curl_close($curl);

        return [
            'responseHttpCode' => $responseHttpCode,
            'redirectUrl' => $redirectUrl,
            'content' => $content,
            'error' => $curlError,
        ];
    }
}