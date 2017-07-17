<?php
namespace In2code\In2template\Migration\Import\PropertyHelper;

use In2code\In2template\Migration\Helper\DatabaseHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CreateRelatedRelationsPropertyHelper
 */
class CreateRelatedRelationsPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @var string
     */
    protected $mmTableName = 'tx_news_domain_model_news_related_mm';

    /**
     * @return void
     */
    protected function manipulate()
    {
        $relatedIdentifiers
            = GeneralUtility::trimExplode(',', $this->getPropertyFromOldRecord($this->getPropertyName()), true);
        if (!empty($relatedIdentifiers)) {
            $databaseHelper = $this->getObjectManager()->get(DatabaseHelper::class);
            foreach ($relatedIdentifiers as $sorting => $relatedIdentifier) {
                $row = [
                    'uid_foreign' => $this->getPropertyFromOldRecord('uid'),
                    'uid_local' => $relatedIdentifier,
                    'sorting' => $sorting
                ];
                $databaseHelper->createRecord($this->mmTableName, $row);
                $this->log->addMessage('new news relation added to news with uid ' . $relatedIdentifier);
            }
            $this->setProperty(count($relatedIdentifiers));
        }
    }
}
