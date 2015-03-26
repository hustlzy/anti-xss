<?php

use voku\helper\AntiXSS;

class XssTest extends PHPUnit_Framework_TestCase {

  /**
   * @var $security AntiXSS
   */
  public $security;

  public function setUp()
  {
    $this->security = new AntiXSS();
  }

  public function test_xss_clean()
  {
    $harm_string = "Hello, i try to <script>alert('Hack');</script> your site";

    $harmless_string = $this->security->xss_clean($harm_string);

    $this->assertEquals("Hello, i try to alert&#40;'Hack'&#41;; your site", $harmless_string);
  }

  public function test_xss_clean_string_array()
  {
    $harm_strings = array(
        "Hello, i try to <script>alert('Hack');</script> your site",
        "Simple clean string",
        "Hello, i try to <script>alert('Hack');</script> your site",
        "<a href=\"http://test.com?param1=\"+onMouseOver%3D\"alert%281%29%3B&step=2&param12=A\">test</a>"
    );

    $this->security->setReplacement('[removed]');
    $harmless_strings = $this->security->xss_clean($harm_strings);
    $this->security->setReplacement('');

    $this->assertEquals("Hello, i try to [removed]alert&#40;'Hack'&#41;;[removed] your site", $harmless_strings[0]);
    $this->assertEquals("Simple clean string", $harmless_strings[1]);
    $this->assertEquals("Hello, i try to [removed]alert&#40;'Hack'&#41;;[removed] your site", $harmless_strings[2]);
    $this->assertEquals("<a href=\"http://test.com?param1=\">test</a>", $harmless_strings[3]);
  }

  public function test_xss_clean_image_valid()
  {
    $harm_string = '<img src="test.png">';

    $xss_clean_return = $this->security->xss_clean($harm_string, TRUE);

    $this->assertTrue($xss_clean_return);
  }

  public function test_xss_clean_image_invalid()
  {
    $harm_string = '<img src=javascript:alert(String.fromCharCode(88,83,83))>';

    $xss_clean_return = $this->security->xss_clean($harm_string, TRUE);

    $this->assertFalse($xss_clean_return);
  }

  public function test_xss_hash()
  {
    $this->assertTrue(preg_match('#^[0-9a-f]{32}$#iS', $this->security->xss_hash()) === 1);
  }

  public function testXssClean()
  {
    // \v (vertical whitespace) isn't working on travis-ci ?

    $testArray = array(
      "onAttribute=\"bar\"" => "\"bar\"",
      "<BGSOUND SRC=\"javascript:alert('XSS');\">" => "&lt;BGSOUND SRC=\"alert&#40;'XSS'&#41;;\"&gt;", // BGSOUND
      "<BR SIZE=\"&{alert('XSS')}\">" => "<BR SIZE=\"\">", // & JavaScript includes
      "<LINK REL=\"stylesheet\" HREF=\"javascript:alert('XSS');\">" => "&lt;LINK REL=\"stylesheet\" HREF=\"alert&#40;'XSS'&#41;;\"&gt;", // STYLE sheet
      "<STYLE>BODY{-moz-binding:url(\"http://ha.ckers.org/xssmoz.xml#xss\")}</STYLE>" => "&lt;STYLE&gt;BODY{:url(\"http://ha.ckers.org/xssmoz.xml#xss\")}&lt;/STYLE&gt;", // Remote style sheet
      "<STYLE>@im\\port'\\jaasc\ript:alert(\"XSS\")';</STYLE>" => "&lt;STYLE&gt;@im\port'\jaasc\ript:alert&#40;\"XSS\"&#41;';&lt;/STYLE&gt;", // STYLE tags with broken up JavaScript for XSS
      "<XSS STYLE=\"xss:expression_r(alert('XSS'))\">" => "", // Anonymous HTML with STYLE attribute
      "<XSS STYLE=\"behavior: url(xss.htc);\">" => "", // Local htc file
      "¼script¾alert(¢XSS¢)¼/script¾" => "¼script¾alert&#40;¢XSS¢&#41;¼/script¾", // US-ASCII encoding
      "<IMG defang_SRC=javascript:alert\(&quot;XSS&quot;\)>" => "<IMG >", // IMG
      "<IMG SRC=javascript:alert(&quot;XSS&quot;)>" => "<IMG >",
      "<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>" => "<IMG >",
      "<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>" => "<IMG >",
      "<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>" => "<IMG >",
      "<IMG SRC=\"jav	ascript:alert('XSS');\">" => "<IMG >",
      "<IMG SRC=\"jav&#x09;ascript:alert('XSS');\">" => "<IMG >",
      "<IMG SRC=\"jav&#x09;ascript:alert&rpar;'XSS'&rpar;;\">" => "<IMG >",
      "<IMG SRC=\"jav&#x0A;ascript:alert('XSS');\">" => "<IMG >",
      "<test lall=&amp;amp;#039;jav&#x0A;ascript:alert(\\&amp;amp;#039;XSS\\&amp;amp;#039;);&amp;amp;#039;>" => "<test lall=&#039;alert&#40;\\&#039;XSS\\&#039;&#41;;&#039;>",
      "<IMG SRC\n=\n\"\nj\na\nv\n&#x0A;a\ns\nc\nr\ni\np\nt\n:\na\nl\ne\nr\nt\n(\n'\nX\nS\nS\n'\n)\n;\">" => "<IMG SRC\n=\n\"\n\nalert\n&#40;\n'\nX\nS\nS\n'\n&#41;\n;\">",
      "<IMG SRC=java�script:alert('XSS')>" => "<IMG >",
      "<DIV STYLE=\"background-image:\\0075\\0072\\006C\\0028'\\006a\\0061\\0076\\0061\\0073\\0063\\0072\\0069\\0070\\0074\\003a\\0061\\006c\\0065\\0072\\0074\\0028\\0027\\0058\\0053\\0053\\0027\\0029'\\0029\">" => "<DIV >",
      "<STYLE>.XSS{background-image:url(\"javascript:alert('XSS')\");}</STYLE><A CLASS=XSS></A>" => "&lt;STYLE&gt;.XSS{background-image:url(\"alert&#40;'XSS'&#41;\");}&lt;/STYLE&gt;&lt;A ></A>",
      "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0;url=javascript:alert('XSS');\">" => "&lt;META HTTP-EQUIV=\"refresh\" CONTENT=\"0;url=alert&#40;'XSS'&#41;;\"&gt;", // META
      "<IFRAME SRC=\"javascript:alert('XSS');\"></IFRAME>" => "&lt;IFRAME SRC=\"alert&#40;'XSS'&#41;;\"&gt;&lt;/IFRAME>", // IFRAME
      "<applet code=A21 width=256 height=256 archive=\"toir.jar\"></applet>" => "&lt;applet code=A21 width=256 height=256 archive=\"toir.jar\"&gt;&lt;/applet>",
      "<script Language=\"JavaScript\" event=\"FSCommand (command, args)\" for=\"theMovie\">...</script>" => "...", // <script>
      "<SCRIPT>document.write(\"<SCRI\");</SCRIPT>PT SRC=\"http://ha.ckers.org/xss.js\"></SCRIPT>" => "(\"<SCRI\");PT SRC=\"http://ha.ckers.org/xss.js\">", // XSS using HTML quote encapsulation
      "<SCR�IPT>alert(\"XSS\")</SCR�IPT>" => "alert&#40;\"XSS\"&#41;",
      "Би шил идэй чадна,<STYLE>li {list-style-image: url(\"javascript:alert('XSS')\");}</STYLE><UL><LI>我能吞下玻璃而不傷身體</br>" => "Би шил идэй чадна,&lt;STYLE&gt;li {list-style-image: url(\"alert&#40;'XSS'&#41;\");}&lt;/STYLE&gt;&lt;UL><LI>我能吞下玻璃而不傷身體</br>",
      "';alert(String.fromCharCode(88,83,83))//';alert(String.fromCharCode(88,83,83))//\"\; alert(String.fromCharCode(88,83,83))//\"\;alert(String.fromCharCode(88,83,83))//--></SCRIPT>\">'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>" => "';alert&#40;String.fromCharCode(88,83,83&#41;)//';alert&#40;String.fromCharCode(88,83,83&#41;)//\"\\; alert&#40;String.fromCharCode(88,83,83&#41;)//\"\\;alert&#40;String.fromCharCode(88,83,83&#41;)//--&gt;\">'>alert&#40;String.fromCharCode(88,83,83&#41;)",
      "म काँच खान सक्छू र मलाई केहि नी हुन्‍न् <IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>।" => "म काँच खान सक्छू र मलाई केहि नी हुन्‍न् <IMG >।",
      "https://[host]/testing?foo=bar&tab=<script>alert('foobar')</script>" => "https://[host]/testing?foo=bar&tab=alert&#40;'foobar'&#41;",
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_qty=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_qty='\">alert&#40;'ImmuniWeb'&#41;;", // XSS to attack "pfSense" - https://www.htbridge.com/advisory/HTB23251
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_protocolflags=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_protocolflags='\">alert&#40;'ImmuniWeb'&#41;;",
      "https://[host]/diag_logs_filter.php?filterlogentries_submit=1&filterlogentries_s ourceport=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.php?filterlogentries_submit=1&filterlogentries_s ourceport='\">alert&#40;'ImmuniWeb'&#41;;",
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_destinationport=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_destinationport='\">alert&#40;'ImmuniWeb'&#41;;",
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_destinationipaddress=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3 E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_destinationipaddress='\">alert&#40;'ImmuniWeb'&#41;;&lt;/script%3 E",
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_sourceport=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_sourceport='\">alert&#40;'ImmuniWeb'&#41;;",
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_sourceipaddress=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_sourceipaddress='\">alert&#40;'ImmuniWeb'&#41;;",
      "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_time=%27%22%3E%3Cscript%3Ealert%28%27ImmuniWeb%27%29;%3C/script%3E" => "https://[host]/diag_logs_filter.phpfilterlogentries_submit=1&filterlogentries_time='\">alert&#40;'ImmuniWeb'&#41;;",
      "http://www.amazon.com/review/R3FSGZJ3NBYZM/?id=brute'-alert('XSSPOSED' )-'logic" => "http://www.amazon.com/review/R3FSGZJ3NBYZM/?id=brute'-alert&#40;'XSSPOSED' &#41;-'logic", // XSS from amazon -> https://www.xssposed.org/search/?search=amazon.com&type=host&
      "User-Agent: </script><svg/onload=alert('xssposed')>" => "User-Agent: &lt;svg/&gt;",
      "https://www.amazon.com/gp/aw/ya/181-1583093-7256013/\"></form><script>a lert('Lohit Tummalapenta')</script>" => "https://www.amazon.com/gp/aw/ya/181-1583093-7256013/\">&lt;/form&gt;alert&#40;'Lohit Tummalapenta'&#41;",
      "https://aws.amazon.com/amis?ami_provider_id=4&amp;architecture='\"--></ style></script><script>alert(0x015E00)</script>&amp;selection=ami_prov ider_id+architecture" => "https://aws.amazon.com/amis?ami_provider_id=4&amp;architecture='\"--&gt;&lt;/ style&gt;alert&#40;0x015E00&#41;&selection=ami_prov ider_id architecture",
      "pipe=ssrProductAds&amp;step=2&amp;userName=1211&amp;replyTo=test%40xssed.com&amp;subjectEscape=&amp;subject=Unable+to+re gister+for+Product+Ads&amp;emailMessageEscape=&amp;emailMessage=&amp;displayName=%27%22%3E%3Ciframe+src%3Dhttp:% 2F%2Fxssed.com%3E&amp;companyURL=&amp;address1=&amp;address2=&amp;city=&amp;state=&amp;zipCode=&amp;country=United+States&amp;ccCard holderName=&amp;ccIssuer=V&amp;addCreditCardNumber=&amp;ccExpMonth=10&amp;ccExpYear=2010&amp;businessAddressCheck=useBus inessAddress&amp;billingAddress1=&amp;billingAddress2=&amp;billingCity=&amp;billingState=&amp;billingZipCode=&amp;billingCou ntry=United+States&amp;Continue=&amp;_pi_legalName=121&amp;_pi_tokenID=A1F3841M9ZHMMV&amp;_pi_pipe=ssrProductAds&amp;_pi _email=kf%40xssed.com&amp;_pi_step=1&amp;_pi_areaCode=112&amp;_pi_phone1=121&amp;_pi_userName=1211&amp;_pi_ext=211221212 1&amp;_pi_phone2=1221" => "pipe=ssrProductAds&step=2&userName=1211&replyTo=test@xssed.com&subjectEscape=&subject=Unable to re gister for Product Ads&emailMessageEscape=&emailMessage=&displayName='\">&lt;iframe src=http:% 2F/xssed.com&gt;&companyURL=&address1=&address2=&city=&state=&zipCode=&country=United States&ccCard holderName=&ccIssuer=V&addCreditCardNumber=&ccExpMonth=10&ccExpYear=2010&businessAddressCheck=useBus inessAddress&billingAddress1=&billingAddress2=&billingCity=&billingState=&billingZipCode=&billingCou ntry=United States&Continue=&_pi_legalName=121&_pi_tokenID=A1F3841M9ZHMMV&_pi_pipe=ssrProductAds&_pi _email=kf@xssed.com&_pi_step=1&_pi_areaCode=112&_pi_phone1=121&_pi_userName=1211&_pi_ext=211221212 1&_pi_phone2=1221",
      "http://www.amazon.com/s?ie=UTF5&amp;keywords=\"><script>alert(document. cookie)</script>" => "http://www.amazon.com/s?ie=UTF5&amp;keywords=\">alert&#40;document. cookie&#41;",
      "http://www.amazon.com/gp/digital/rich-media/media-player.html?ie=UTF8& amp;location=javascript:alert(1)&amp;ASIN=B000083JTS" => "http://www.amazon.com/gp/digital/rich-media/media-player.html?ie=UTF8& amp;location=alert&#40;1&#41;&ASIN=B000083JTS",
      "http://r-images.amazon.com/s7ondemand/brochure/flash_brochure.jsp?comp any=ama1&amp;sku=AtHome7&amp;windowtitle=XSS&lt;/title&gt;&lt;script/s rc=//z.l.to&gt;&lt;/script&gt;&lt;plaintext&gt;" => "http://r-images.amazon.com/s7ondemand/brochure/flash_brochure.jsp?comp any=ama1&sku=AtHome7&windowtitle=XSS&lt;/title&gt;&lt;plaintext>",
      "https://sellercentral.amazon.com/gp/change-password/change-password-em ail.html?errorMessage=I'm%20sorry,%20the%20Password%20Assistance%20pag e%20is%20temporarily%20unavailable.%20%20Please%20try%20again%20in%201 5%2" => "https://sellercentral.amazon.com/gp/change-password/change-password-em ail.html?errorMessage=I'm sorry, the Password Assistance pag e is temporarily unavailable.  Please try again in 1 5%2",
      "http://www.amazon.com/s/ref=amb_link_7189562_72/002-2069697-5560831?ie =UTF8&amp;node=&quot;/&gt;&lt;script&gt;alert('XSS');&lt;/script&gt;&a mp;pct-off=25-&amp;hidden-keywords=athletic|outdoor&amp;pf_rd_m=ATVPDK IKX0DER&amp;pf_rd_s=center-5&amp;pf_r" => "http://www.amazon.com/s/ref=amb_link_7189562_72/002-2069697-5560831?ie =UTF8&node=\"/>alert&#40;'XSS'&#41;;&a mp;pct-off=25-&hidden-keywords=athletic|outdoor&pf_rd_m=ATVPDK IKX0DER&pf_rd_s=center-5&pf_r",
      "https://sellercentral.amazon.com/gp/on-board/workflow/Registration/log in.html?passthrough/&amp;passthrough/account=soa\"><script>alert(\"XSS\") </script>&amp;passthrough/superSource=OAR&amp;passthrough/marketplaceI D=ATVPDKI" => "https://sellercentral.amazon.com/gp/on-board/workflow/Registration/log in.html?passthrough/&amp;passthrough/account=soa\">alert&#40;\"XSS\"&#41; &passthrough/superSource=OAR&passthrough/marketplaceI D=ATVPDKI",
      "http://sellercentral.amazon.com/gp/seller/product-ads/registration.htm l?ld=\"><script>alert(document.cookie)</script>" => "http://sellercentral.amazon.com/gp/seller/product-ads/registration.htm l?ld=\">alert&#40;&#41;",
      "https://sellercentral.amazon.com/gp/change-password/-\"><script>alert(d ocument.cookie)</script>-.html" => "https://sellercentral.amazon.com/gp/change-password/-\">alert&#40;&#41;-.html",
      "http://www.amazon.com/script-alert-product-document-cookie/dp/B003H777 5E/ref=sr_1_3?s=gateway&amp;ie=UTF8&amp;qid=1285870078&amp;sr=8-3" => "http://www.amazon.com/script-alert-product-document-cookie/dp/B003H777 5E/ref=sr_1_3?s=gateway&ie=UTF8&qid=1285870078&sr=8-3",
      "http://www.amazon.com/s/ref=sr_a9ps_home/?url=search-alias=aps&amp;tag =amzna9-1-20&amp;field-keywords=-\"><script>alert(document.cookie)</scr ipt>" => "http://www.amazon.com/s/ref=sr_a9ps_home/?url=search-alias=aps&amp;tag =amzna9-1-20&amp;field-keywords=-\">alert&#40;&#41;",
      "http://www.amazon.com/s/ref=amb_link_7581132_5/102-9803838-3100108?ie= UTF8&amp;node=&quot;/&gt;&lt;script&gt;alert(&quot;XSS&quot;);&lt;/scr ipt&gt;&amp;keywords=Lips&amp;emi=A19ZEOAOKUUP0Q&amp;pf_rd_m=ATVPDKIKX 0DER&amp;pf_rd_s=left-1&amp;pf_rd_r=1JMP7" => "http://www.amazon.com/s/ref=amb_link_7581132_5/102-9803838-3100108?ie= UTF8&node=\"/>alert&#40;\"XSS\"&#41;;&keywords=Lips&emi=A19ZEOAOKUUP0Q&pf_rd_m=ATVPDKIKX 0DER&pf_rd_s=left-1&pf_rd_r=1JMP7",
      "http://askville.amazon.com/SearchRequests.do?search=\"></script><script >alert('XSS')</script>&amp;start=0&amp;max=10&amp;open=true&amp;closed =true&amp;x=18&amp;y=7" => "http://askville.amazon.com/SearchRequests.do?search=\">alert&#40;'XSS'&#41;&start=0&max=10&open=true&closed =true&x=18&y=7",
      "https://sellercentral.amazon.com/gp/seller/registration/login.html?ie= UTF8&amp;email=&amp;errors=<script src=http://ha.ckers.org/xss.js?/>&amp;userName=&amp;tokenID=AO9UIQIH15 TE" => "https://sellercentral.amazon.com/gp/seller/registration/login.html?ie= UTF8&amp;email=&amp;errors=&userName=&tokenID=AO9UIQIH15 TE",
      "https://sellercentral.amazon.com/gp/seller/registration/login.html?ie= UTF8&amp;email=<script src=http://ha.ckers.org/xss.js?/>&amp;userName=&amp;tokenID=AO9UIQIH15 TE" => "https://sellercentral.amazon.com/gp/seller/registration/login.html?ie= UTF8&amp;email=&userName=&tokenID=AO9UIQIH15 TE",
      "address-daytime-phone=&amp;address-daytime-phone-areacode=%24Q%24%2F%3E&amp;address-daytime-phone-ext=&amp;pipel ine-return-directly=1&amp;pipeline-return-handler=fx-pay-pages%2Fmanage-pay-pages%2F&amp;pipeline-return-han dler-type=post&amp;pipeline-return-html=fx%2Fhelp%2Fgetting-started.html&amp;pipeline-type=payee&amp;register-bi lling-address-id=jgmhpujplj&amp;register-credit-card-id=A1V46DGTZUE15I&amp;register-enter-checking-info=no&amp;r egister-epay-registration-status-check=no&amp;register-nickname=pg5of16&amp;register-payment-program=tipping &amp;input-address-daytime-phone-areacode=%22%2F%3E%3Cscript+src%3Dhttp%3A%2F%2Fha.ckers.org%2Fxss.js%3F %2F%3E&amp;input-address-daytime-phone=&amp;input-address-daytime-phone-ext=&amp;input-register-nickname=xss&amp;inp ut-register-enter-checking-info=no&amp;x=0&amp;y=0" => "address-daytime-phone=&address-daytime-phone-areacode=\$Q$/>&address-daytime-phone-ext=&pipel ine-return-directly=1&pipeline-return-handler=fx-pay-pages/manage-pay-pages/&pipeline-return-han dler-type=post&pipeline-return-html=fx/help/getting-started.html&pipeline-type=payee&register-bi lling-address-id=jgmhpujplj&register-credit-card-id=A1V46DGTZUE15I&register-enter-checking-info=no&r egister-epay-registration-status-check=no&register-nickname=pg5of16&register-payment-program=tipping &input-address-daytime-phone-areacode=\"/>&input-address-daytime-phone=&input-address-daytime-phone-ext=&input-register-nickname=xss&inp ut-register-enter-checking-info=no&x=0&y=0",
      "c=A2H6YBKBHMURHR&amp;t=1&amp;o=4&amp;process_form=1&amp;email_address=%22%2F%3E%3Cscript+src%3Dhttp%3A%2F%2Fha.ckers .org%2Fxss.js%3F%2F%3E&amp;password=&amp;x=0&amp;y=0" => "c=A2H6YBKBHMURHR&t=1&o=4&process_form=1&email_address=\"/>&password=&x=0&y=0",
      "https://affiliate-program.amazon.com/gp/associates/help/glossary/'>\">< SCRIPT/SRC=http://kusomiso.com/xss.js></SCRIPT>" => "https://affiliate-program.amazon.com/gp/associates/help/glossary/'>\">&lt; SCRIPT/SRC=http://kusomiso.com/xss.js&gt;",
      "https://affiliate-program.amazon.com/gp/associates/help/main.html/'>\"> <SCRIPT/SRC=http://kusomiso.com/xss.js></SCRIPT>" => "https://affiliate-program.amazon.com/gp/associates/help/main.html/'>\"> ",
      "http://www.amazon.com/gp/daily/ref=\"/><script>alert('XSS $4.99 S&amp;H')</script>" => "http://www.amazon.com/gp/daily/ref=\"/>alert&#40;'XSS $4.99 S&H'&#41;",
    );

    foreach ($testArray as $before => $after) {
      $this->assertEquals($after, $this->security->xss_clean($before));
    }
  }

  public function testJavaScriptCleaning()
  {
    // http://cpansearch.perl.org/src/KURIANJA/HTML-Defang-1.02/t/02_xss.t

    $testArray = array(
        "<img FSCommand=\"someFunction()\">",
        "<img onAbort=\"someFunction()\">",
        "<img onActivate=\"someFunction()\">",
        "<img onAfterPrint=\"someFunction()\">",
        "<img onAfterUpdate=\"someFunction()\">",
        "<img onBeforeActivate=\"someFunction()\">",
        "<img onBeforeCopy=\"someFunction()\">",
        "<img onBeforeCut=\"someFunction()\">",
        "<img onBeforeDeactivate=\"someFunction()\">",
        "<img onBeforeEditFocus=\"someFunction()\">",
        "<img onBeforePaste=\"someFunction()\">",
        "<img onBeforePrint=\"someFunction()\">",
        "<img onBeforeUnload=\"someFunction()\">",
        "<img onBegin=\"someFunction()\">",
        "<img onBlur=\"someFunction()\">",
        "<img onBounce=\"someFunction()\">",
        "<img onCellChange=\"someFunction()\">",
        "<img onChange=\"someFunction()\">",
        "<img onClick=\"someFunction()\">",
        "<img onContextMenu=\"someFunction()\">",
        "<img onControlSelect=\"someFunction()\">",
        "<img onCopy=\"someFunction()\">",
        "<img onCut=\"someFunction()\">",
        "<img onDataAvailable=\"someFunction()\">",
        "<img onDataSetChanged=\"someFunction()\">",
        "<img onDataSetComplete=\"someFunction()\">",
        "<img onDblClick=\"someFunction()\">",
        "<img onDeactivate=\"someFunction()\">",
        "<img onDrag=\"someFunction()\">",
        "<img onDragEnd=\"someFunction()\">",
        "<img onDragLeave=\"someFunction()\">",
        "<img onDragEnter=\"someFunction()\">",
        "<img onDragOver=\"someFunction()\">",
        "<img onDragDrop=\"someFunction()\">",
        "<img onDrop=\"someFunction()\">",
        "<img onEnd=\"someFunction()\">",
        "<img onError=\"someFunction()\">",
        "<img onErrorUpdate=\"someFunction()\">",
        "<img onFilterChange=\"someFunction()\">",
        "<img onFinish=\"someFunction()\">",
        "<img onFocus=\"someFunction()\">",
        "<img onFocusIn=\"someFunction()\">",
        "<img onFocusOut=\"someFunction()\">",
        "<img onHelp=\"someFunction()\">",
        "<img onKeyDown=\"someFunction()\">",
        "<img onKeyPress=\"someFunction()\">",
        "<img onKeyUp=\"someFunction()\">",
        "<img onLayoutComplete=\"someFunction()\">",
        "<img onLoad=\"someFunction()\">",
        "<img onLoseCapture=\"someFunction()\">",
        "<img onMediaComplete=\"someFunction()\">",
        "<img onMediaError=\"someFunction()\">",
        "<img onMouseDown=\"someFunction()\">",
        "<img onMouseEnter=\"someFunction()\">",
        "<img onMouseLeave=\"someFunction()\">",
        "<img onMouseMove=\"someFunction()\">",
        "<img onMouseOut=\"someFunction()\">",
        "<img onMouseOver=\"someFunction()\">",
        "<img onMouseUp=\"someFunction()\">",
        "<img onMouseWheel=\"someFunction()\">",
        "<img onMove=\"someFunction()\">",
        "<img onMoveEnd=\"someFunction()\">",
        "<img onMoveStart=\"someFunction()\">",
        "<img onOutOfSync=\"someFunction()\">",
        "<img onPaste=\"someFunction()\">",
        "<img onPause=\"someFunction()\">",
        "<img onProgress=\"someFunction()\">",
        "<img onPropertyChange=\"someFunction()\">",
        "<img onReadyStateChange=\"someFunction()\">",
        "<img onRepeat=\"someFunction()\">",
        "<img onReset=\"someFunction()\">",
        "<img onResize=\"someFunction()\">",
        "<img onResizeEnd=\"someFunction()\">",
        "<img onResizeStart=\"someFunction()\">",
        "<img onResume=\"someFunction()\">",
        "<img onReverse=\"someFunction()\">",
        "<img onRowsEnter=\"someFunction()\">",
        "<img onRowExit=\"someFunction()\">",
        "<img onRowDelete=\"someFunction()\">",
        "<img onRowInserted=\"someFunction()\">",
        "<img onScroll=\"someFunction()\">",
        "<img onSeek=\"someFunction()\">",
        "<img onSelect=\"someFunction()\">",
        "<img onSelectionChange=\"someFunction()\">",
        "<img onSelectStart=\"someFunction()\">",
        "<img onStart=\"someFunction()\">",
        "<img onStop=\"someFunction()\">",
        "<img onSyncRestored=\"someFunction()\">",
        "<img onSubmit=\"someFunction()\">",
        "<img onTimeError=\"someFunction()\">",
        "<img onTrackChange=\"someFunction()\">",
        "<img onUnload=\"someFunction()\">",
        "<img onURLFlip=\"someFunction()\">",
        "<img seekSegmentTime=\"someFunction()\">",
    );

    foreach ($testArray as $test) {
      $this->assertEquals("<img >", $this->security->xss_clean($test));
    }
  }

  public function test_xss_clean_entity_double_encoded()
  {
    $testArray = array(
        "<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>" => "<IMG >",
        "<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>" => "<IMG >",
        "<IMG SRC=\"jav&#x09;ascript:alert('XSS');\">" => "<IMG >",
        "<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>" => "<IMG >",
        "<a href=\"&#38&#35&#49&#48&#54&#38&#35&#57&#55&#38&#35&#49&#49&#56&#38&#35&#57&#55&#38&#35&#49&#49&#53&#38&#35&#57&#57&#38&#35&#49&#49&#52&#38&#35&#49&#48&#53&#38&#35&#49&#49&#50&#38&#35&#49&#49&#54&#38&#35&#53&#56&#38&#35&#57&#57&#38&#35&#49&#49&#49&#38&#35&#49&#49&#48&#38&#35&#49&#48&#50&#38&#35&#49&#48&#53&#38&#35&#49&#49&#52&#38&#35&#49&#48&#57&#38&#35&#52&#48&#38&#35&#52&#57&#38&#35&#52&#49\">Clickhere</a>" => "<a >Clickhere</a>",
        "<a href=\"http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D\">Google</a>" => "<a href=\"http://www.google.com\">Google</a>",
    );

    foreach ($testArray as $before => $after) {
      $this->assertEquals($after, $this->security->xss_clean($before));
    }
  }

  public function test_xss_clean_js_img_removal()
  {
    $input = '<img src="&#38&#35&#49&#48&#54&#38&#35&#57&#55&#38&#35&#49&#49&#56&#38&#35&#57&#55&#38&#35&#49&#49&#53&#38&#35&#57&#57&#38&#35&#49&#49&#52&#38&#35&#49&#48&#53&#38&#35&#49&#49&#50&#38&#35&#49&#49&#54&#38&#35&#53&#56&#38&#35&#57&#57&#38&#35&#49&#49&#49&#38&#35&#49&#49&#48&#38&#35&#49&#48&#50&#38&#35&#49&#48&#53&#38&#35&#49&#49&#52&#38&#35&#49&#48&#57&#38&#35&#52&#48&#38&#35&#52&#57&#38&#35&#52&#49">Clickhere';
    $this->assertEquals('<img >', $this->security->xss_clean($input));
  }

  public function test_xss_clean_sanitize_naughty_html()
  {
    $input = '<blink>';
    $this->assertEquals('&lt;blink&gt;', $this->security->xss_clean($input));
  }

  public function test_remove_evil_attributes()
  {
    $this->assertEquals('onAttribute="bar"', $this->security->remove_evil_attributes('onAttribute="bar"', false));
    $this->assertEquals('<foo >', $this->security->remove_evil_attributes('<foo onAttribute="bar">', false));
    $this->assertEquals('<foo >', $this->security->remove_evil_attributes('<foo onAttributeNoQuotes=bar>', false));
    $this->assertEquals('<foo >', $this->security->remove_evil_attributes('<foo onAttributeWithSpaces = bar>', false));
    $this->assertEquals('<foo prefixOnAttribute="bar">', $this->security->remove_evil_attributes('<foo prefixOnAttribute="bar">', false));
    $this->assertEquals('<foo>onOutsideOfTag=test</foo>', $this->security->remove_evil_attributes('<foo>onOutsideOfTag=test</foo>', false));
    $this->assertEquals('onNoTagAtAll = true', $this->security->remove_evil_attributes('onNoTagAtAll = true', false));
  }

  /**
   * all tests from drupal
   */
  public function testXss() {

    $cases = array(
      // Tag stripping, different ways to work around removal of HTML tags.
        array(
            '<script>alert(0)</script>',
            'alert&#40;0&#41;',
            'script',
            'HTML tag stripping -- simple script without special characters.',
        ),
        array(
            '<script src="http://www.example.com" />',
            '',
            'script',
            'HTML tag stripping -- empty script with source.',
        ),
        array(
            '<ScRipt sRc=http://www.example.com/>',
            '',
            'script',
            'HTML tag stripping evasion -- varying case.',
        ),
        array(
            "<script\nsrc\n=\nhttp://www.example.com/\n>",
            '',
            'script',
            'HTML tag stripping evasion -- multiline tag.',
        ),
        array(
            '<script/a src=http://www.example.com/a.js></script>',
            '',
            'script',
            'HTML tag stripping evasion -- non whitespace character after tag name.',
        ),
        array(
            '<script/src=http://www.example.com/a.js></script>',
            '',
            'script',
            'HTML tag stripping evasion -- no space between tag and attribute.',
        ),
      // Null between < and tag name works at least with IE6.
        array(
            "<\0scr\0ipt>alert(0)</script>",
            'alert&#40;0&#41;',
            'ipt',
            'HTML tag stripping evasion -- breaking HTML with nulls.',
        ),
        array(
            "<scrscriptipt src=http://www.example.com/a.js>",
            '<scrscriptipt src=http://www.example.com/a.js>',
            'script',
            'HTML tag stripping evasion -- filter just removing "script".',
        ),
        array(
            '<<script>alert(0);//<</script>',
            '&lt;alert&#40;0&#41;;//&lt;',
            'script',
            'HTML tag stripping evasion -- double opening brackets.',
        ),
        array(
            '<script src=http://www.example.com/a.js?<b>',
            '',
            'script',
            'HTML tag stripping evasion -- no closing tag.',
        ),
      // DRUPAL-SA-2008-047: This doesn't seem exploitable, but the filter should
      // work consistently.
        array(
            '<script>>',
            '>',
            'script',
            'HTML tag stripping evasion -- double closing tag.',
        ),
        array(
            '<script src=//www.example.com/.a>',
            '',
            'script',
            'HTML tag stripping evasion -- no scheme or ending slash.',
        ),
        array(
            '<script src=http://www.example.com/.a',
            '&lt;script src=http://www.example.com/.a',
            'script',
            'HTML tag stripping evasion -- no closing bracket.',
        ),
        array(
            '<script src=http://www.example.com/ <',
            '&lt;script src=http://www.example.com/ &lt;',
            'script',
            'HTML tag stripping evasion -- opening instead of closing bracket.',
        ),
        array(
            '<nosuchtag attribute="newScriptInjectionVector">',
            '<nosuchtag attribute="newScriptInjectionVector">',
            'nosuchtag',
            'HTML tag stripping evasion -- unknown tag.',
        ),
        array(
            '<t:set attributeName="innerHTML" to="&lt;script defer&gt;alert(0)&lt;/script&gt;">',
            '<t:set attributeName="innerHTML" to="alert&#40;0&#41;">',
            't:set',
            'HTML tag stripping evasion -- colon in the tag name (namespaces\' tricks).',
        ),
        array(
            '<img """><script>alert(0)</script>',
            '<img """><>',
            'script',
            'HTML tag stripping evasion -- a malformed image tag.',
            array('img'),
        ),
        array(
            '<blockquote><script>alert(0)</script></blockquote>',
            '<blockquote>alert&#40;0&#41;</blockquote>',
            'script',
            'HTML tag stripping evasion -- script in a blockqoute.',
            array('blockquote'),
        ),
        array(
            "<!--[if true]><script>alert(0)</script><![endif]-->",
            '&lt;!--[if true]>alert&#40;0&#41;<![endif]--&gt;',
            'script',
            'HTML tag stripping evasion -- script within a comment.',
        ),
      // Dangerous attributes removal.
        array(
            '<p onmouseover="http://www.example.com/">',
            '<p >',
            'onmouseover',
            'HTML filter attributes removal -- events, no evasion.',
            array('p'),
        ),
        array(
            '<li style="list-style-image: url(javascript:alert(0))">',
            '<li -image: url(alert&#40;0&#41;)">',
            'style',
            'HTML filter attributes removal -- style, no evasion.',
            array('li'),
        ),
        array(
            '<img onerror   =alert(0)>',
            '<img >',
            'onerror',
            'HTML filter attributes removal evasion -- spaces before equals sign.',
            array('img'),
        ),
        array(
            '<img onabort!#$%&()*~+-_.,:;?@[/|\]^`=alert(0)>',
            '<img >',
            'onabort',
            'HTML filter attributes removal evasion -- non alphanumeric characters before equals sign.',
            array('img'),
        ),
        array(
            '<img oNmediAError=alert(0)>',
            '<img >',
            'onmediaerror',
            'HTML filter attributes removal evasion -- varying case.',
            array('img'),
        ),
      // Works at least with IE6.
        array(
            "<img o\0nfocus\0=alert(0)>",
            '<img >',
            'focus',
            'HTML filter attributes removal evasion -- breaking with nulls.',
            array('img'),
        ),
      // Only whitelisted scheme names allowed in attributes.
        array(
            '<img src="javascript:alert(0)">',
            '<img >',
            'javascript',
            'HTML scheme clearing -- no evasion.',
            array('img'),
        ),
        array(
            '<img src=javascript:alert(0)>',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- no quotes.',
            array('img'),
        ),
      // A bit like CVE-2006-0070.
        array(
            '<img src="javascript:confirm(0)">',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- no alert ;)',
            array('img'),
        ),
        array(
            '<img src=`javascript:alert(0)`>',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- grave accents.',
            array('img'),
        ),
        array(
            '<img dynsrc="javascript:alert(0)">',
            '<img >',
            'javascript',
            'HTML scheme clearing -- rare attribute.',
            array('img'),
        ),
        array(
            '<table background="javascript:alert(0)">',
            '<table background="alert&#40;0&#41;">',
            'javascript',
            'HTML scheme clearing -- another tag.',
            array('table'),
        ),
        array(
            '<base href="javascript:alert(0);//">',
            '&lt;base href="alert&#40;0&#41;;//"&gt;',
            'javascript',
            'HTML scheme clearing -- one more attribute and tag.',
            array('base'),
        ),
        array(
            '<img src="jaVaSCriPt:alert(0)">',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- varying case.',
            array('img'),
        ),
        array(
            '<img src=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#48;&#41;>',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- UTF-8 decimal encoding.',
            array('img'),
        ),
        array(
            '<img src=&#00000106&#0000097&#00000118&#0000097&#00000115&#0000099&#00000114&#00000105&#00000112&#00000116&#0000058&#0000097&#00000108&#00000101&#00000114&#00000116&#0000040&#0000048&#0000041>',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- long UTF-8 encoding.',
            array('img'),
        ),
        array(
            '<img src=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x30&#x29>',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- UTF-8 hex encoding.',
            array('img'),
        ),
        array(
            "<img src=\"jav\tascript:alert(0)\">",
            '<img >',
            'script',
            'HTML scheme clearing evasion -- an embedded tab.',
            array('img'),
        ),
        array(
            '<img src="jav&#x09;ascript:alert(0)">',
            '<img >',
            'script',
            'HTML scheme clearing evasion -- an encoded, embedded tab.',
            array('img'),
        ),
        array(
            '<img src="jav&#x000000A;ascript:alert(0)">',
            '<img >',
            'script',
            'HTML scheme clearing evasion -- an encoded, embedded newline.',
            array('img'),
        ),
      // With &#xD; this test would fail, but the entity gets turned into
      // &amp;#xD;, so it's OK.
        array(
            '<img src="jav&#x0D;ascript:alert(0)">',
            '<img >',
            'script',
            'HTML scheme clearing evasion -- an encoded, embedded carriage return.',
            array('img'),
        ),
        array(
            "<img src=\"\n\n\nj\na\nva\ns\ncript:alert(0)\">",
            '<img >',
            'cript',
            'HTML scheme clearing evasion -- broken into many lines.',
            array('img'),
        ),
        array(
            "<img src=\"jav\0a\0\0cript:alert(0)\">",
            '<img >',
            'cript',
            'HTML scheme clearing evasion -- embedded nulls.',
            array('img'),
        ),
        array(
            '<img src="vbscript:msgbox(0)">',
            '<img src="msgbox(0)">',
            'vbscript',
            'HTML scheme clearing evasion -- another scheme.',
            array('img'),
        ),
        array(
            '<img src="nosuchscheme:notice(0)">',
            '<img src="nosuchscheme:notice(0)">',
            'nosuchscheme',
            'HTML scheme clearing evasion -- unknown scheme.',
            array('img'),
        ),
      // Netscape 4.x javascript entities.
        array(
            '<br size="&{alert(0)}">',
            '<br size="">',
            'alert',
            'Netscape 4.x javascript entities.',
            array('br'),
        ),
      // DRUPAL-SA-2008-006: Invalid UTF-8, these only work as reflected XSS with
      // Internet Explorer 6.
        array(
            "<p arg=\"\xe0\">\" style=\"background-image: url(j\xe0avas\xc2\xa0cript:alert(0));\"\xe0<p>",
            '<p arg="">" style="background-image: url(alert&#40;0&#41;);"<p>',
            'style',
            'HTML filter -- invalid UTF-8.',
            array('p'),
        ),
        array(
            '<img src=" &#14;  javascript:alert(0)">',
            '<img >',
            'javascript',
            'HTML scheme clearing evasion -- spaces and metacharacters before scheme.',
            array('img'),
        ),
    );

    foreach ($cases as $caseArray) {
      $this->assertEquals($caseArray[1], $this->security->xss_clean($caseArray[0]), 'error by: ' . $caseArray[0]);
    }
  }


}