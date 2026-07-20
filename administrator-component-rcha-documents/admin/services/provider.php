<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_rcha_documents
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Topoweryou\Component\RchaDocuments\Administrator\Extension\RchaDocumentsComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Topoweryou\\Component\\RchaDocuments'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Topoweryou\\Component\\RchaDocuments'));
        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new RchaDocumentsComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
