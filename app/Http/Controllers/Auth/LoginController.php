<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Dusterio\LumenPassport\Http\Controllers\AccessTokenController;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;

class LoginController extends Controller
{
    /**
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * @var TokenRepository
     */
    protected $tokens;

    /**
     * @var JwtParser
     */
    protected $jwt;

    /**
     * Create a new controller instance.
     *
     * @param AuthorizationServer $server
     * @param TokenRepository $tokens
     * @param JwtParser $jwt
     */
    public function __construct(AuthorizationServer $server,
                                TokenRepository $tokens,
                                JwtParser $jwt)
    {
        $this->server = $server;
        $this->tokens = $tokens;
        $this->jwt = $jwt;
    }

    /**
     * Handle a login request to the application and return access token.
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    public function login(ServerRequestInterface $request)
    {
        $request = $request->withParsedBody($request->getParsedBody() +
            [
                'grant_type' => 'password',
                'client_id' => config('passport.client_id'),
                'client_secret' => config('passport.client_secret')
            ]);

        $controller = new AccessTokenController($this->server, $this->tokens, $this->jwt);

        return with($controller->issueToken($request));
    }
}
