<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper;

use In2code\In2template\Migration\Helper\FileHelper;
use In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper\YouTubePluginConfigurationFlexFormHelper;
use In2code\In2template\Migration\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class VideoPluginPropertyHelper
 */
class VideoPluginPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $fileLocation = 'fileadmin/user_upload/';

    /**
     * @return void
     */
    public function manipulate()
    {
        $this->createYoutubeFileAndRelation();
        $this->changeContentElementToMedia();
    }

    /**
     * @return void
     */
    protected function changeContentElementToMedia()
    {
        $this->setProperties(['CType' => 'textmedia']);
    }

    /**
     * @return void
     */
    protected function createYoutubeFileAndRelation()
    {
        $uri = $this->getYoutubeUri();
        $identifier = $this->createYoutubeFileAndIndex($uri);
        $this->createFileReference($identifier);
    }

    /**
     * @param int $identifier
     * @return void
     */
    protected function createFileReference(int $identifier)
    {
        $fileHelper = $this->getObjectManager()->get(FileHelper::class);
        $fileHelper->createFileRelation(
            'tt_content',
            'assets',
            $this->getPropertyFromRecord('uid'),
            $identifier
        );
    }

    /**
     * @param string $uri
     * @return int
     */
    protected function createYoutubeFileAndIndex(string $uri): int
    {
        $pathAndFilename = $this->getPathAndFileName($uri);
        $youtubeCode = StringUtility::getYoutubeCodeFromUri($uri);
        GeneralUtility::writeFile($pathAndFilename, $youtubeCode);
        $fileHelper = $this->getObjectManager()->get(FileHelper::class);
        $identifier = $fileHelper->tryToIndexFile(0, $this->getPathAndFileName($uri, false), $this->getProperties());
        return $identifier;
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @return string absolute path and filename like /var/www/fileadmin/filename.ext
     */
    protected function getPathAndFileName(string $uri, bool $absolute = true): string
    {
        $filename = $this->getFileNameFromUriTitle($uri);
        $pathAndFileName = $this->fileLocation . $filename;
        if ($absolute) {
            $pathAndFileName = GeneralUtility::getFileAbsFileName($pathAndFileName);
        }
        return $pathAndFileName;
    }

    /**
     * Read title tag content from Youtube URL and create a filename like
     * Title_Video.youtube
     *
     * @param string $uri
     * @return string
     */
    protected function getFileNameFromUriTitle(string $uri): string
    {
        $name = md5(time());
        $content = GeneralUtility::getUrl($uri);
        preg_match_all('~<title>(.*)</title>~U', $content, $result);
        if (!empty($result[1][0])) {
            $string = str_replace(' ', '_', $result[1][0]);
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $string);
        }
        $name .= '.youtube';
        return $name;
    }

    /**
     * @return string
     */
    protected function getYoutubeUri(): string
    {
        $youtubeHelper = $this->getObjectManager()->get(
            YouTubePluginConfigurationFlexFormHelper::class,
            $this,
            $this->configuration
        );
        $uri = $youtubeHelper->getVariable();
        return $uri;
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return $this->getPropertyFromRecord('CType') === 'list'
            && $this->getPropertyFromRecord('list_type') === 'udgmvvideo_videoplayer';
    }
}
