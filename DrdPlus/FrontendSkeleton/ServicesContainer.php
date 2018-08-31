<?php
declare(strict_types=1);

namespace DrdPlus\FrontendSkeleton;

use DeviceDetector\Parser\Bot as BotParser;
use DrdPlus\FrontendSkeleton\Web\Body;
use DrdPlus\FrontendSkeleton\Web\Head;
use DrdPlus\FrontendSkeleton\Web\Menu;
use DrdPlus\FrontendSkeleton\Web\WebFiles;
use Granam\Strict\Object\StrictObject;

class ServicesContainer extends StrictObject
{

    /** @var WebVersions */
    protected $webVersions;
    /** @var Configuration */
    protected $configuration;
    /** @var HtmlHelper */
    protected $htmlHelper;
    /** @var PageCache */
    protected $pageCache;
    /** @var Head */
    protected $head;
    /** @var Menu */
    protected $menu;
    /** @var Body */
    protected $body;
    /** @var CssFiles */
    protected $cssFiles;
    /** @var WebFiles */
    protected $webFiles;
    /** @var Request */
    protected $request;
    /** @var BotParser */
    protected $botParser;
    /** @var CookiesService */
    private $cookiesService;

    public function __construct(Configuration $configuration, HtmlHelper $htmlHelper)
    {
        $this->configuration = $configuration;
        $this->htmlHelper = $htmlHelper;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getWebVersions(): WebVersions
    {
        if ($this->webVersions === null) {
            $this->webVersions = new WebVersions($this->getConfiguration(), $this->getRequest());
        }

        return $this->webVersions;
    }

    public function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = new Request($this->getBotParser());
        }

        return $this->request;
    }

    public function getBotParser(): BotParser
    {
        if ($this->botParser === null) {
            $this->botParser = new BotParser();
        }

        return $this->botParser;
    }

    public function getHtmlHelper(): HtmlHelper
    {
        return $this->htmlHelper;
    }

    public function getPageCache(): PageCache
    {
        if ($this->pageCache === null) {
            $this->pageCache = new PageCache(
                $this->getWebVersions(),
                $this->getConfiguration()->getDirs(),
                $this->getHtmlHelper()->isInProduction()
            );
        }

        return $this->pageCache;
    }

    public function getMenu(): Menu
    {
        if ($this->menu === null) {
            $this->menu = new Menu($this->getConfiguration(), $this->getWebVersions(), $this->getRequest());
        }

        return $this->menu;
    }

    public function getHead(): Head
    {
        if ($this->head === null) {
            $this->head = new Head($this->getConfiguration(), $this->getHtmlHelper(), $this->getCssFiles());
        }

        return $this->head;
    }

    public function getBody(): Body
    {
        if ($this->body === null) {
            $this->body = new Body($this->getWebFiles());
        }

        return $this->body;
    }

    public function getCssFiles(): CssFiles
    {
        if ($this->cssFiles === null) {
            $this->cssFiles = new CssFiles($this->getHtmlHelper()->isInProduction(), $this->getConfiguration()->getDirs());
        }

        return $this->cssFiles;
    }

    public function getDirs(): Dirs
    {
        return $this->getConfiguration()->getDirs();
    }

    public function getWebFiles(): WebFiles
    {
        if ($this->webFiles === null) {
            $this->webFiles = new WebFiles($this->getDirs(), $this->getWebVersions());
        }

        return $this->webFiles;
    }

    public function getCookiesService(): CookiesService
    {
        if ($this->cookiesService === null) {
            $this->cookiesService = new CookiesService();
        }

        return $this->cookiesService;
    }

}