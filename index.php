<?php
require_once("vendor/autoload.php");

use Aws\S3\S3Client;
use Aws\S3\Crypto\S3EncryptionClient;
use Aws\Kms\KmsClient;
use Aws\Crypto\KmsMaterialsProvider;

use Aws\Exception\AwsException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define("AWS_ACCESS_KEY_ID",     getenv("AWS_ACCESS_KEY_ID"));
define("AWS_SECRET_ACCESS_KEY", getenv("AWS_SECRET_ACCESS_KEY"));
define("GENERATORKEYID",        getenv("GENERATORKEYID"));
define("KEYIDS",                getenv("KEYIDS"));

define("S3BUCKETNAME", 'Your_lovely_bucket_name_in_amazon_s3');

$sharedOptions = [
    // 'profile' => 'default',
    'region' => 'ap-east-1',
    'version' => 'latest',
    'credentials' => [
        'key'    => AWS_ACCESS_KEY_ID,
        'secret' => AWS_SECRET_ACCESS_KEY,
    ]
];

// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {

    $target_file = basename($_FILES["fileToUpload"]["name"]);

    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
    }

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        // print "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";

        // Let's construct our S3EncryptionClient using an S3Client
        $encryptionClient = new S3EncryptionClient(new S3Client($sharedOptions));
        // print "<pre>"; var_export($encryptionClient); print "</pre>";

        // This materials provider handles generating a cipher key and
        // initialization vector, as well as encrypting your cipher key via AWS KMS
        $kmsKeyArn = KEYIDS;
        $materialsProvider = new KmsMaterialsProvider(new KmsClient($sharedOptions), $kmsKeyArn);
        // print "<pre>"; var_export($materialsProvider); print "</pre>";

        // $bucket = 'the-bucket-name';
        // $key = 'the-file-name';
        $cipherOptions = [
            'Cipher' => 'gcm',
            'KeySize' => 256,
            // Additional configuration options
        ];

        $result = $encryptionClient->putObject([
            '@MaterialsProvider' => $materialsProvider,
            '@CipherOptions' => $cipherOptions,
            'Bucket' => S3BUCKETNAME,
            'Key' => $target_file,
            // 'Body' => fopen($target_file, 'r'),
            'Body' => "",
            'SourceFile' => $target_file
        ]);

        // print "<pre>"; var_export($result); print "</pre>";
        // Decryption
        $result = $encryptionClient->getObject([
            '@MaterialsProvider' => $materialsProvider,
            '@CipherOptions' => [
                // Additional configuration options
            ],
            'Bucket' => S3BUCKETNAME,
            'Key' => $target_file,
        ]);
        // print "<pre>"; var_export($result); print "</pre>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title></title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="encrypt.b.js"></script>
</head>
<body>
<form name="testupload" action="<?=$_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload" />
    <input type="submit" value="Upload Image" name="submit" />
</form>

<script type="text/javascript">
myEncryptBundle.encrypt("Text to encrypt")
    .then((r) => {
        console.log("[myEncryptBundle]encrypted: ", r);
        // document.write("<p>encryptionBundle: " + r + "</p>");

        // Decrypt value
        let p_decrypted = myEncryptBundle.decrypt(r)
                            .then((decrypted) => {
                                console.log("[myEncryptBundle]decrypted: ", decrypted)
                                // document.write("<p>decryptionBundle: " + decrypted + "</p>");
                            })
                            .catch((err) => {
                                console.log("[myEncryptBundle:decrypt:catch]err: ", err);
                            });
    })
    .catch((err) => {
        console.log("[myEncryptBundle:encrypt:catch]err: ", err);
    });
</script>

</body></html>