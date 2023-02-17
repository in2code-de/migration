<?php
declare(strict_types=1);
namespace In2code\Migration\Port\Service;

use Doctrine\DBAL\DBALException;
use In2code\Migration\Migration\Helper\FileHelper;
use In2code\Migration\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LinkRelationService
 * to extend the export json with files that are linked from a RTE
 */
class LinkRelationService
{
    /**
     * Hold the complete configuration like
     *
     *  'excludedTables' => [
     *      'be_users'
     *  ],
     *  'linkMapping' => [
     *      'propertiesWithLinks' => [
     *          'tt_content' => [
     *              'bodytext'
     *          ]
     *      ],
     *      'propertiesWithRelations' => [
     *          [
     *              'field' => 'header_link',
     *              'table' => 'pages'
     *          ]
     *      ],
     *      'propertiesWithRelationsInFlexForms' => [
     *          'tt_content' => [
     *              'pi_flexform' => [
     *                  [
     *                      // tt_news update flexform
     *                      'condition' => [
     *                          'Ctype' => 'list',
     *                          'list_type' => 9
     *                      ],
     *                      'selection' => '//T3FlexForms/data/sheet[@index="s_misc"]/language/field[@index="PIDitemDisplay"]/value',
     *                      'table' => 'pages'
     *                  ]
     *              ]
     *          ]
     *      ]
     *  ],
     *  'addFilesFromFileadminLinks' => [
     *      'paths' => [
     *          'fileadmin/'
     *      ]
     *  ]
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * LinkRelationService constructor.
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param array $jsonArray
     * @return int[]
     * @throws DBALException
     */
    public function getFileIdentifiersFromLinks(array $jsonArray): array
    {
        $identifiers = [];
        foreach ($this->getPropertiesWithLinks() as $table => $fields) {
            if (!empty($jsonArray['records'][$table])) {
                foreach ($jsonArray['records'][$table] as $row) {
                    foreach ($fields as $field) {
                        if (!empty($row[$field])) {
                            $identifiers = array_merge($identifiers, $this->searchForFileLinks($row[$field]));
                            $identifiers = array_merge($identifiers, $this->searchForClassicFileLinks($row[$field]));
                        }
                    }
                }
            }
        }
        return $identifiers;
    }

    /**
     * Search for links in RTE text like "<a href="t3://file?uid=123">link</a>"
     *
     * @param string $content
     * @return int[] file identifiers
     */
    protected function searchForFileLinks(string $content): array
    {
        preg_match_all('~t3://file\?uid=(\d+)~', $content, $result);
        if (!empty($result[1])) {
            return ArrayUtility::intArray($result[1]);
        }
        return [];
    }

    /**
     * Search for oldschool links in RTE text like:
     *  <a href="fileadmin/file.pdf">link</a> OR
     *  <img src="fileadmin/image.jpg">
     *
     * @param string $content
     * @return int[]
     * @throws DBALException
     */
    protected function searchForClassicFileLinks(string $content): array
    {
        $folders = implode('|', $this->configuration['addFilesFromFileadminLinks']['paths']);
        preg_match_all('~(href|src)="((' . $folders . ')([^"]+))"~', $content, $result);
        if (count($result[0]) > 0) {
            $files = [];
            foreach (array_keys($result[0]) as $key) {
                $identifier = $result[4][$key];
                $storageFolder = $result[3][$key];
                $fileHelper = GeneralUtility::makeInstance(FileHelper::class);
                $storageIdentifier = $fileHelper->findIdentifierFromStoragePath($storageFolder);
                $file = $fileHelper->findFileIdentifierFromIdentifierAndStorage(
                    $this->cleanFilePath($identifier),
                    $storageIdentifier
                );
                if ($file > 0) {
                    $files[] = $file;
                }
            }
            return $files;
        }
        return [];
    }

    /**
     * Clean double slashes
     *
     * @param string $filePath
     * @return string
     */
    protected function cleanFilePath(string $filePath): string
    {
        return str_replace('//', '/', $filePath);
    }

    /**
     * @return array
     */
    protected function getPropertiesWithLinks(): array
    {
        return (array)$this->configuration['linkMapping']['propertiesWithLinks'];
    }
}
