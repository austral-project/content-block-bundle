##php##
/*
 * This file is part of the Austral ContentBlock Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity\Austral\ContentBlockBundle;
use Austral\ContentBlockBundle\Entity\Guideline as BaseGuideline;

use Doctrine\ORM\Mapping as ORM;

/**
 * Austral Guideline Entity.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @ORM\Table(name="austral_content_block_guideline")
 * @ORM\Entity(repositoryClass="Austral\ContentBlockBundle\Repository\GuidelineRepository")
 * @ORM\HasLifecycleCallbacks
 * @final
 */
class Guideline extends BaseGuideline
{
  public function __construct()
  {
    parent::__construct();
  }
}
