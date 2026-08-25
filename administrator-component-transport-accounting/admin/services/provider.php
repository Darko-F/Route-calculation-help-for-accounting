<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_transport_accounting
 *
 * @copyright   Copyright (C) 2026 topoweryou.com
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Topoweryou\Component\TransportAccounting\Administrator\Extension\TransportAccountingComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Topoweryou\\Component\\TransportAccounting'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Topoweryou\\Component\\TransportAccounting'));
        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new TransportAccountingComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};
