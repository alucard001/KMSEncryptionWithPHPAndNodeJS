// exports.testWorks = function testWorks(){
//     console.log("it works");
//     document.write("It is really work.");
// }

module.exports = {
    encrypt: async function(plainText){
        const { encrypt } = require("@aws-crypto/client-browser")
        const { toBase64 } = require("@aws-sdk/util-base64-browser")

        // console.log("[Encrypt]Plaintext: ", plainText)

        const {keyring, context} = initKMS();
        // console.log("keyring: ", keyring, "context: ", context);

        // https://stackoverflow.com/a/37902334/1802483
        // Since encrypt will only accept Uint8Array, to get this value you need to use TextEncoder to create it
        let enc = new TextEncoder();
        const { result } = await encrypt(keyring, enc.encode(plainText), { encryptionContext: context });

        let b64result = toBase64(result)
        // console.log("[Encrypt]b64result: ", b64result, "len: ", b64result.length);

        return b64result;
    },
    decrypt: async function(b64text){
        const { decrypt } = require("@aws-crypto/client-browser")
        const { fromBase64 } = require("@aws-sdk/util-base64-browser")

        // console.log("[Decrypt]b64text: ", b64text)

        const {keyring, context} = initKMS();
        // console.log("keyring: ", keyring, "context: ", context);

        /* Decrypt the result using the same keyring */
        const { plaintext, messageHeader } = await decrypt(keyring, fromBase64(b64text))

        /* Get the encryption context */
        const { encryptionContext } = messageHeader
        // console.log("encryptionContext: ", encryptionContext)

        let dec = new TextDecoder();
        let decodePlainText = dec.decode(plaintext)

        // console.log(plaintext, messageHeader)
        console.log("[Decrypt]decodePlainText: ", decodePlainText)

        return decodePlainText;
    },
}

function initKMS(){
    // Or require("dotenv").config()
    require("dotenv").config({ path: "./.env" })

    const { KmsKeyringBrowser, KMS, getClient } = require("@aws-crypto/client-browser")
    // const { fromBase64, toBase64 } = require("@aws-sdk/util-base64-browser")
    // console.log(KmsKeyringBrowser, KMS, getClient, encrypt, decrypt, b64Browser);

    const accessKeyId = process.env.AWS_ACCESS_KEY_ID;
    const secretAccessKey = process.env.AWS_SECRET_ACCESS_KEY;
    const generatorKeyId = process.env.GENERATORKEYID;
    const keyIds = [process.env.KEYIDS];
    const clientProvider = getClient(KMS, {
        credentials: {
            accessKeyId,
            secretAccessKey
        }
    })

    const keyring = new KmsKeyringBrowser({
        clientProvider: clientProvider,
        generatorKeyId: generatorKeyId,
        keyIds: keyIds,
    })
    // console.log(keyring);

    const context = {
        stage: 'demo',
        purpose: 'simple demonstration app',
        origin: 'ap-east-1',
    }

    return {keyring, context}
}