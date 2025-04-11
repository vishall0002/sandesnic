<?php

/*
 * This file is part of the CCDNUser SecurityBundle
 *
 * (c) CCDN (c) CodeConsortium <http://www.codeconsortium.com/>
 *
 * Available on github <http://www.github.com/codeconsortium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 *
 * @category CCDNUser
 * @package  SecurityBundle
 *
 * @author   Reece Fowell <reece@codeconsortium.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  Release: 2.0
 * @link     https://github.com/codeconsortium/CCDNUserSecurityBundle
 *
 */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface{

    /**
     *
     * @access protected
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router $router
     */
    protected $router;

    /**
     *
     * @param array $routeReferer
     */
    protected $routeReferer;

    /**
     *
     * @param array $routeLogin
     */
    protected $routeLogin;

    /**
     *
     * @param array $emr
     */
    protected $container;

    /**
     *
     * @access public
     * @param \Symfony\Bundle\FrameworkBundle\Routing\Router $router
     * @param array                                          $routeReferer
     * @param array                                          $routeLogin
     */
    public function __construct(Router $router, $routeReferer, $routeLogin, $serviceContainer) {
        $this->router = $router;
        $this->routeReferer = $routeReferer;
        $this->routeLogin = $routeLogin;
        $this->container = $serviceContainer;
    }

    /**
     *
     * @access public
     * @param  \Symfony\Component\HttpFoundation\Request                                                     $request
     * @param  \Symfony\Component\Security\Core\Authentication\Token\TokenInterface                          $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token) {


        if ($this->routeReferer['enabled']) {
            $key = '_security.main.target_path';

            if ($this->container->get('session')->has($key)) {
                $url = $this->container->get('session')->get($key);
                $this->container->get('session')->remove($key);
            } else {
                $url = $this->router->generate('login_authorisation');
            }

            $response = new RedirectResponse($url);

            if ($request->isXmlHttpRequest() || $request->request->get('_format') === 'json') {
                $response = new Response(json_encode(array('status' => 'success')));
                $response->headers->set('Content-Type', 'application/json');
            }
        } else {
            if ($request->isXmlHttpRequest() || $request->request->get('_format') === 'json') {
                $response = new Response(
                        json_encode(
                                array(
                                    'status' => 'failed',
                                    'errors' => array()
                                )
                        )
                );

                $response->headers->set('Content-Type', 'application/json');
            } else {
                $response = new RedirectResponse(
                        $this->router->generate(
                                $this->routeLogin['name'], $this->routeLogin['params']
                        )
                );
            }
        }
        return $response;
    }

}
