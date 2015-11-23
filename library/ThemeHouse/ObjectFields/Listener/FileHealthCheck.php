<?php

class ThemeHouse_ObjectFields_Listener_FileHealthCheck
{

    public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes = array_merge($hashes,
            array(
                'library/ThemeHouse/ObjectFields/ControllerAdmin/ObjectField.php' => '76032506228efae83efcd888825ab483',
                'library/ThemeHouse/ObjectFields/DataWriter/ObjectField.php' => '23fe848b48c5bba851999bcb49ddd843',
                'library/ThemeHouse/ObjectFields/DataWriter/ObjectFieldGroup.php' => '8512820de7cb398fba197228ac3d6bd3',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/ControllerAdmin/Class.php' => 'de9b90ce7f63e96277d2f4c6129787d9',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/ControllerAdmin/Object.php' => '1b017f408bd5985f9c7586528363b47d',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/ControllerPublic/Class.php' => 'ecabcbc673346b5f7ec90510f4da6820',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/ControllerPublic/Object.php' => '60e05f7d52d3dd384ab218b370b8b090',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/DataWriter/Class.php' => 'f5d38fe0b1a35176eda1fc0cdaea41ef',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/DataWriter/Object.php' => 'a4322956fb308cb371977337f3374647',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/Install.php' => '6dd610bfe73d074196c1a0f87869269e',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/Model/Class.php' => '1c28128d8d1a63ca7f37a176f4b525e8',
                'library/ThemeHouse/ObjectFields/Extend/ThemeHouse/Objects/Model/Object.php' => 'c1ff00e41190fae17fbb572a2bb4298e',
                'library/ThemeHouse/ObjectFields/Extend/XenForo/Model/AddOn.php' => 'af6f41d4638381e5f015131e2bf453ce',
                'library/ThemeHouse/ObjectFields/Install/Controller.php' => '408b56b275479ce11ac5191ebe0f20b6',
                'library/ThemeHouse/ObjectFields/Listener/InitDependencies.php' => 'd81ff6b24a9d40bf7e17a7323501ec20',
                'library/ThemeHouse/ObjectFields/Listener/LoadClassController.php' => 'e9e86c977c1482e80ee4549158f68def',
                'library/ThemeHouse/ObjectFields/Listener/LoadClassDataWriter.php' => 'c13925ef63ed3b9df47bec8aab6df142',
                'library/ThemeHouse/ObjectFields/Listener/LoadClassInstallerThemeHouse.php' => '837c1b8003e0875fe6467df9a6b0c224',
                'library/ThemeHouse/ObjectFields/Listener/LoadClassModel.php' => '2ac10d6651393e564124356f3e19a379',
                'library/ThemeHouse/ObjectFields/Listener/TemplateHook.php' => '54b4c4d501bbbb412bfb57ab9171d08d',
                'library/ThemeHouse/ObjectFields/Listener/TemplatePostRender.php' => '69ebbb35311b0c2221c7e0e8aa5aa396',
                'library/ThemeHouse/ObjectFields/Model/ObjectField.php' => '496ca6d69f5ad8ec11577579ef2360ac',
                'library/ThemeHouse/ObjectFields/Route/PrefixAdmin/ObjectFields.php' => '9c88c05ce16d012e610af3227d1b6c86',
                'library/ThemeHouse/ObjectFields/ViewAdmin/ObjectField/Export.php' => 'b32eddf1554a5eb17b1d599bf0841623',
                'library/ThemeHouse/Install.php' => '18f1441e00e3742460174ab197bec0b7',
                'library/ThemeHouse/Install/20151109.php' => '2e3f16d685652ea2fa82ba11b69204f4',
                'library/ThemeHouse/Deferred.php' => 'ebab3e432fe2f42520de0e36f7f45d88',
                'library/ThemeHouse/Deferred/20150106.php' => 'a311d9aa6f9a0412eeba878417ba7ede',
                'library/ThemeHouse/Listener/ControllerPreDispatch.php' => 'fdebb2d5347398d3974a6f27eb11a3cd',
                'library/ThemeHouse/Listener/ControllerPreDispatch/20150911.php' => 'f2aadc0bd188ad127e363f417b4d23a9',
                'library/ThemeHouse/Listener/InitDependencies.php' => '8f59aaa8ffe56231c4aa47cf2c65f2b0',
                'library/ThemeHouse/Listener/InitDependencies/20150212.php' => 'f04c9dc8fa289895c06c1bcba5d27293',
                'library/ThemeHouse/Listener/LoadClass.php' => '5cad77e1862641ddc2dd693b1aa68a50',
                'library/ThemeHouse/Listener/LoadClass/20150518.php' => 'f4d0d30ba5e5dc51cda07141c39939e3',
                'library/ThemeHouse/Listener/Template.php' => '0aa5e8aabb255d39cf01d671f9df0091',
                'library/ThemeHouse/Listener/Template/20150106.php' => '8d42b3b2d856af9e33b69a2ce1034442',
                'library/ThemeHouse/Listener/TemplateHook.php' => 'a767a03baad0ca958d19577200262d50',
                'library/ThemeHouse/Listener/TemplateHook/20150106.php' => '71c539920a651eef3106e19504048756',
                'library/ThemeHouse/Listener/TemplatePostRender.php' => 'b6da98a55074e4cde833abf576bc7b5d',
                'library/ThemeHouse/Listener/TemplatePostRender/20150106.php' => 'efccbb2b2340656d1776af01c25d9382',
            ));
    }
}