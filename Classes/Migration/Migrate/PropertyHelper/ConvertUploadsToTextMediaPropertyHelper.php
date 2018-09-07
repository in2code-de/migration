<?php
namespace In2code\Migration\Migration\Migrate\PropertyHelper;

/**
 * Class ConvertUploadsToTextMediaPropertyHelper
 */
class ConvertUploadsToTextMediaPropertyHelper extends AbstractPropertyHelper implements PropertyHelperInterface
{

    /**
     * @return void
     */
    public function manipulate()
    {
        $this->setProperty($this->getBodytext());
    }

    /**
     * @return string
     */
    protected function getBodytext(): string
    {
        $bodytext = '';
        foreach ($this->getRelatedFileProperties() as $fileProperties) {
            $bodytext .= '<a href="t3://file?uid=' . $fileProperties['uid_local']. '">'
                . $this->getReadableName($fileProperties). '</a>';
            $bodytext .= PHP_EOL;
        }
        return rtrim($bodytext, PHP_EOL);
    }

    /**
     * @param array $fileProperties
     * @return string
     */
    protected function getReadableName(array $fileProperties): string
    {
        $name = $fileProperties['title'];
        if (empty($name)) {
            $row = $this->getDatabase()->exec_SELECTgetSingleRow(
                'name',
                'sys_file',
                'uid=' . (int)$fileProperties['uid_local']
            );
            if (!empty($row['name'])) {
                $name = $row['name'];
            } else {
                $name = $fileProperties['uid_local'];
            }
        }

        return $name;
    }

    /**
     * @return array
     */
    protected function getRelatedFileProperties(): array
    {
        return (array)$this->getDatabase()->exec_SELECTgetRows(
            'uid_local,title',
            'sys_file_reference',
            'tablenames="tt_content" and fieldname="media" and uid_foreign=' . (int)$this->getPropertyFromRecord('uid')
        );
    }

    /**
     * @return bool
     */
    public function shouldMigrate(): bool
    {
        return $this->getPropertyFromRecord('CType') === 'uploads' && $this->getPropertyFromRecord('media') > 0;
    }
}
