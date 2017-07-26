<?php
namespace In2code\In2template\Migration\Migrate\PropertyHelper\FlexFormHelper;

/**
 * Class GetYouTubeCodeFromPluginConfigurationFlexFormHelper
 */
class YouTubePluginConfigurationFlexFormHelper extends AbstractFlexFormHelper implements FlexFormHelperInterface
{

    /**
     * @return string
     */
    public function getVariable(): string
    {
        $recordUid = $this->getRelatedRecordUidFromFlexForm();
        if ($recordUid > 0) {
            $uri = $this->getYoutubeUriFromRecord($recordUid);
            return $uri;
        }
        return '';
    }

    /**
     * @param int $recordUid
     * @return string
     */
    protected function getYoutubeUriFromRecord(int $recordUid): string
    {
        $row = (array)$this->getDatabase()->exec_SELECTgetSingleRow(
            'you_tube_url',
            'tx_udgmvvideo_domain_model_video',
            'uid=' . $recordUid
        );
        return $row['you_tube_url'];
    }

    /**
     * @return int
     */
    protected function getRelatedRecordUidFromFlexForm(): int
    {
        $configuration = $this->getFlexFormArray();
        return (int)$configuration['settings']['viewSingle'];
    }
}
