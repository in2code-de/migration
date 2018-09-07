<?php
namespace In2code\Migration\Command;

use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Alex Kellner <alexander.kellner@in2code.de>, in2code.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class HelpCommandController adds some helper commands to the system
 */
class HelpCommandController extends CommandController
{

    /**
     * Returns a list of the current pid and all sub-pids (could be useful for further database operations)
     *
     * @param int $startPid
     * @return string
     * @cli
     */
    public function getListsOfSubPagesCommand($startPid)
    {
        $queryGenerator = $this->objectManager->get(QueryGenerator::class);
        return $queryGenerator->getTreeList($startPid, 20, 0, 1);
    }
}
