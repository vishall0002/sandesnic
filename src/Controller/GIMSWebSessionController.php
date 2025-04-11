<?php

namespace App\Controller;

use App\Services\DefaultValue;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class GIMSWebSessionController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    private $userSession;

    public function __construct(DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, SessionInterface $userSession)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->userSession = $userSession;
    }

    /**
     * @Route("/gws", name="usr_gims_web_session")
     */
    public function gimsWebSession(Request $request, LoggerInterface $generalapiLogger)
    {
        // return new JsonResponse($this->userSession->get('GIMS-WEB-SESSION'));
        $generalapiLogger->info('GIMSWEB - RAW Request '.print_r($request->request->all(), true));
        $sparams = base64_decode($request->request->get('sparams'));
        $generalapiLogger->info('GIMSWEB - Params received as it is'.$sparams);
        $sparams_vals = \explode(';', $sparams);
        $aeskey = trim($sparams_vals[0]);
        $iv = trim($sparams_vals[1]);
        $generalapiLogger->info('GIMSWEB - Params EKey '.$aeskey);
        $generalapiLogger->info('GIMSWEB - Params IV '.$iv);
        $dataToEncrypt = $this->userSession->get('GIMS-WEB-SESSION');
        $generalapiLogger->info('GIMSWEB - data to encrypt '.print_r($dataToEncrypt, true));
        $output = openssl_encrypt($dataToEncrypt, 'AES-256-CBC', $aeskey, OPENSSL_RAW_DATA, $iv);
        $output = base64_encode($output);
        // $output = chunk_split(base64_encode($output));
        $generalapiLogger->info('GIMSWEB - output '.$output);
        return new JsonResponse(['_l' => $output]);
    }

    /**
     * @Route("/public/gws", name="usr_gims_web_sessiontrial")
     */
    public function gimsWebSessionTrial(Request $request)
    {

        return new JsonResponse($this->userSession->get('GIMS-WEB-SESSION'));
  
        $sparams = base64_decode($request->request->get('sparams'));
        $sparams_vals = \explode(';', $sparams);
        $aeskey = trim($sparams_vals[0]);
        $iv = trim($sparams_vals[1]);
        print_r(\strlen(trim($iv)));
        print_r($sparams_vals);
        $dataToEncrypt = 'SyamKrishna';
        print_r($dataToEncrypt);
        $output = openssl_encrypt($dataToEncrypt, 'AES-256-CBC', $aeskey, OPENSSL_RAW_DATA, $iv);
        $output = base64_encode($output);

        return new JsonResponse(['_l' => $output]);
    }
    /**
     * @Route("/public/gwst", name="usr_gims_web_sessiontrial_t")
     */
    public function gimsWebSessionTrialT(Request $request)
    {
        $src_str = "kZDaoCBUZyzdRS0F5m8V8hgLaNEaC3DzOSiXhBfDqlq79Vw4cn4E/1NTK15nmmkVjB560l/oGLAD pDpcnNoKq2PWlXG8dMDTFR+NmE0nFNQQZUtjagcmx4X8VJ7HENXU7wML3XheKEugXTBOqzfr4uN8 D4pGkRAGpIQe2WG2kaW4e3GzfPozn/PadUhRuod7qroDSClQTP7Wn8DPmo6WmRwHOjM5XcaaRmZz YSjzkhxl0RfbQALB3E2o2vJdRQm50+U7nyfs+Cb48wasNn2fPwZlhi8AZyMSynWFNl7xkIBolBEJ YdXXB6cfRzhiU4pfa2gqgjJmGFD0S+YXixFTboqIIUKhg6/Bl4cNU9Ym7PReqiFCPnxya7McPd5O M3N1njRB7X3G6DCsSmM4+uApZbx4kItT2jWLGQ59rzHoL49WfYk6/jJNIM90W0umvOmyvvo4Dx+O SdueandvFjEE514rtE3TDD2DAB8Rv5jJs8JcWi9zg3YpYk9aDC4h1QsRNszbvw35psZHldwV4fGw pnBct3POfJYAgIXWqriC2jWDhMt8K0mjEZw89399sXGqTkJCJnti5srqXYrPd+9CUUUKrIThX4mK t5YNm5MHf3jCa/XKWbDV9845OWWnBuzl9ptKmuCNfDjqdzEdm58PP0GMxaoHXJM+B2I/v/mD2OyZ jhq+Q6CTxKS36tMJOiOQAVndP5q/7UHn7DPeXJGj6Sv9faS3MIbOr5DlcguYNb3WsgnxaOqNZBQk yRfAheRB";
        $aeskey = 'acefe96d17fb7227b537f258c82b3aef';
        $iv = 'c515eb8836817851';
        $dataToEncrypt = \base64_decode($src_str);
        // print_r($dataToEncrypt);
        $output = openssl_decrypt($dataToEncrypt, 'AES-256-CBC', $aeskey, OPENSSL_RAW_DATA, $iv);
        $output = json_decode($output);

        
        return new JsonResponse(['_l' => $output]);
    }
}
