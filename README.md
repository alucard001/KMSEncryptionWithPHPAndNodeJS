# Key Management System (KMS) with Encrypt/Decrypt using NodeJS, Encrypt uploaded file with PHP

## Two objectives:

- Use [Amazon KMS](https://docs.aws.amazon.com/encryption-sdk/latest/developer-guide/js-examples.html) in JS/NodeJS to encrypt/decrypt a string
- Use KMS again to upload file (image) to Amazon S3 using PHP.

### About PHP upload to S3 using KMS

I managed to do it all in a single `index.php` file.  Most of the PHP upload code should be familiar to you.

#### Include all necessary packages:

```
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
?>
```

About Amazon S3, please note that instead of using `new S3Client($sharedOptions);`, I added an additional wrapper called `S3EncryptionClient`: `new S3EncryptionClient(new S3Client($sharedOptions));`.  `$sharedOptions` is a normal array.  Please refer to the code for details.

In order to use KMS, you need to define `KmsClient` and `KmsMaterialsProvider`, with your `$kmsKeyArn` value defined in `.env`, all in a line:

`new KmsMaterialsProvider(new KmsClient($sharedOptions), $kmsKeyArn);`

As you can see, I am using `.env`, which means I am also using [phpdotenv](https://github.com/vlucas/phpdotenv).

After that you just use the usual `putObject([])` function to upload your file:

```
$result = $encryptionClient->putObject([
    '@MaterialsProvider' => $materialsProvider,
    '@CipherOptions' => $cipherOptions,
    'Bucket' => S3BUCKETNAME,
    'Key' => $target_file,
    'Body' => "",
    'SourceFile' => $target_file
]);
```

In case you don't know where to find some of the variables, don't panic.  Everything is in the `index.php` file.

Once completed, go to your Amazon S3 bucket and find your file.

### KMS encrypt/decrypt using JS/NodeJS

I would personally skip over the panic/frustration of making this [KMS for Javascript](https://docs.aws.amazon.com/encryption-sdk/latest/developer-guide/javascript-installation.html) works.  But here are some key points I want to mention:

- I am using NodeJS latest version (14.x)
- Since I expect that my colleague will use this KMS thing just like a normal JS library like jQuery, I can't assume that this is a NodeJS module and doing a lot of `import`/`export`/`require` things.  People don't use JS that way.
- Because of the above, I am using [browserify](http://browserify.org/) and watchify.  The purpose is to make the encryption script written in NodeJS to be available in normal HTML file
- Since browserify did not work well with dotenv, I added [dotenvify](https://www.npmjs.com/package/@sethvincent/dotenvify).
- So, the final script of building a HTML-usable JS file is follow:

  - Development: `watchify .\encrypt.js --standalone myEncryptBundle -t @sethvincent/dotenvify -o encrypt.b.js -v`
  - Production: `browserify .\encrypt.js --standalone myEncryptBundle -t @sethvincent/dotenvify -o encrypt.b.js -v`

- The final built file is called `encrypt.b.js`, `b` is for `bundle`, but you can use any name to build this file
- Go back to `index.php`, you will see a line like this `myEncryptBundle.encrypt("Text to encrypt")`, this is how the NodeJS function is called.
- Since `encrypt` and `decrypt` are an `async` function, which means a `Promise` is return, not the actual value, which also means that you need to use `then()` to handle the return value.
- The returned value is a `base64` value.  Before base64, it is a `UintArray`.  So I use `TextEncoder` to do encode/decode.
- All the necessary code are in `encrypt.js`.  Please refer to that file for details.
- **DANGER**: Since it is used on the front-end (browser) level, it is (at present) **unavoidable to expose all the AWS keys to public**.  Unless you are configuring suitable rights for account, please use it with caution.

#### KMS in JS

There are something I want to mention here:

- `context`, according to [amazon](https://docs.aws.amazon.com/encryption-sdk/latest/developer-guide/concepts.html#encryption-context), it will store **non-secret** information.
