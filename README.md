# wallet-sdk-php
 Blockchain Wallet SDK includes APIs for managing wallet accounts(Decentralized Identifiers, DID), digital assets(Proof of Existence, POE), colored tokens etc.  You need not care about how the backend blockchain runs or the unintelligible techniques, such as consensus, endorsement and decentralization. Simply use the SDK we provide to implement your business logics, we will handle the caching, tagging, compressing, encrypting and high availability.

# 使用说明

## 1. 注册wallet客户端
```code
$host:arxan-chain wallet服务的ip与port
$api_key:注册企业账户返回的api=key
$cert_path:秘钥与证书目录
$signParam{
    $creator string; // 企业账户id (必填)
    $nonce string;  // 随机数   (必填)
    $private_key string; // 企业账户私钥 (必填)
}:账户签名对象

$client = new WalletClient($host,$api_key,$cert_path,$signParam);
```

## 2. 注册wallet账户
```code
$register = new RegisterWalletBody("type","access","secret"); 
    type string, //类型(必填)
    access string, //账户名
    secret string, //账户密码(必填)
    phone" string , //电话
    email string ", //邮箱

$ret = $client->register($register,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["id"]=> "did:axn:53915ff7-16b4-432e-b3f4-8d3cb44b5240", //分配给钱包的唯一ID
        ["endpoint"]=> "06aa7f0690ef573a9bde61d312ff54036e267e45e856571215e043862d788058", //分配给钱包的地址
        ["created"]=> 1529906262, //创建钱包的时间戳
        ["token_id"]=> "",
        ["security_code"]=> "我是中国人", //秘钥安全码，用于检索秘钥
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",
            "05759e0b0c6c7e76faf1902598270480ab006612678dbd0396ed427a86a81bc0",
            "e92561919b9c790fbc18b17cac41887449993ab3bf9d4567192818528caa0c1d"
        }
    }
}
```

## 3.创建数字资产存证
```code
$poe = new POEBody("name",owner);
    name string(必填) // 资产名称
    owner string(必填)  // 所有者
    parent_id string    
    hash" string
    metadata string
);

$signature{
    creator string; // 创建者
    nonce string;   // 随机数
    private_key string;     // 私钥(可以不传)
}

$security_code //注册wallet账户返回的秘钥安全码，owner

$ret = $client->createPOE($poe,$signature,$security_code,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["id"]=> "did:axn:c9f0a2a0-8428-49da-b606-28c66baa1423", //资产ID
        ["created"]=> 1529906262, //创建资产的时间戳
        ["token_id"]=> "993773421ce32574491a86b69a001e30da11350bf162c49ff8e8e71972ca0143",//用于交易
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",
        }
    }
}
```

## 4.发行数字凭证
```code
$ctoken = new IssueCTokenBody ("issuer","owner","poe",amount); 
{
    issuer string,(必填) //发行者
    owner string //拥有者 
    asset_id string,(必填) // 资产id
    amount string,(必填) //数量
)

$security_code //注册wallet账户返回的秘钥安全码，issuer

$ret = $client->issuerCToken($token,$signature,$security_code，$token_res); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["id"]=> "did:axn:c9f0a2a0-8428-49da-b606-28c66baa1423", //资产ID
        ["created"]=> 1529906262, //创建资产的时间戳
        ["token_id"]=> "4575a3209a8d9144b59b4e8d9a87288efc8eac2d0d4a935209a64dd2cf6ab228", 发行的数字凭证ID,用于转让
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",
        }
    }
}
```

## 5.发行数字资产
```code
$asset = new IssueAssetBody ("issuer","owner","poe")
{
    issuer string,(必填) // 发行者
    owner string,(必填) // 所有者
    asset_id string,(必填)  // 资产
)

$security_code //注册wallet账户返回的秘钥安全码，issuer

$ret = $client->issuerAsset($asset,$signature,$security_code，$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["created"]=> 1529906262, //创建资产的时间戳
        ["token_id"]=> "", 
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",
    }
}
```

## 6.转让资产
```code
$data = new TransferAssetBody ("from","to","assets")
{
    from string,(必填)
    to string,(必填)
    assets array,(必填) // 资产数组
)

$security_code //注册wallet账户返回的秘钥安全码，from

$ret = $client->transferAsset($data,$signature,$security_code,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["created"]=> 0,
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",//交易id
    }
}
```

## 7.转让数字凭证
```code
$data = new TransferCTokenBody ("from","to",tokens)
    from,(必填)
    to,(必填)
    tokens array(token_amount) (必填) // token 数组
    token_amount{
        token_id string,(必填)issuerCToken 返回的token_id;
        amount number,(必填)
    }
)

$security_code //注册wallet账户返回的秘钥安全码，from

$ret = $client->transferCToken($data,$signature,$security_code,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["created"]=> 0, //创建资产的时间戳
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",//交易id
    }
}
```

## 8.获取账户信息
```code
$did : 账户id
$client->getWalletInfo($did,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["id"]=> "did:axn:66b16790-2668-45d8-8e35-add9609d0ae0", 
        ["endpoint"]=> "83348be84d26f0e3618d64802ac314584cf0e8243cf11975a2af440148fead16", 
        ["status"]=> "Valid",
        ["created"]=> 1529907096, //创建资产的时间戳
        ["updated"]=> 0,
        ["hds"]=> NULL;
}
```

## 9.查询钱包余额
```code
$did : 账户id
$client->getWalletBalance($did,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["colored_tokens"]=>{
            ["6ae580d924845508c541e09ec3d3f40fc0a3a4747790899abc18a333d879aa90"]=>{
                ["id"]=>"6ae580d924845508c541e09ec3d3f40fc0a3a4747790899abc18a333d879aa90",
                ["amount"]=>10,
            }
        }
        ["digital_assets"]=>{
            ["did:axn:32b01897-091f-4833-8296-b7c706be92d2"]=>{
                ["id"]=> "did:axn:32b01897-091f-4833-8296-b7c706be92d2",
                ["amount"]=> 1,
                ["name"]=> "",
                ["status"]=> 0,
            }
        }
}
```

## 10.查询钱包余额
```code
$did : 账户id
$type : 类型，in表示输入，out表示输出,""表示获取所有

$client->tranfserTxn($did,$type,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["endpoint-001"]=> {
            ["utxo"] => [
                {
                    ["sourceTxDataHash"]=> "source-tx-data-hash",
                    ["ix"]=> 1,
                    ["ctokenId"]=> "ctokenid-001",
                    ["value"]=> 5,
                    ["addr"]=> "endpoint-who-will-receive this txout",
                    ["until"]=> -1,
                    ["script"]=> payload data be attached to this tx",
                    ["createdAt"]=> {
                        ["seconds"]=> 5555555,
                        ["nanos"]=> 0,
                    },
                    ["founder"]=> "funder-did-0001",
                    ["txType"]=> 0,
                }
            ],
            ["stxo"]=> [
                {
                    ["sourceTxDataHash"]=> "source-tx-data-hash",
                    ["ix"]=> 2,
                    ["ctokenId"]=> "ctokenid-001",
                    ["value"]=> 5,
                    ["addr"]=> "endpoint-who-will-receive this txout",
                    ["until"]=> -1,
                    ["script"]=> payload data be attached to this tx",
                    ["createdAt"]=> {
                        ["seconds"]=> 6666666,
                        ["nanos"]=> 0,
                    },
                    ["spentTxDataHash"]=> "spent-tx-data-hash",
                    ["spentAt"]=> {
                        ["seconds"]=> 6666667,
                        ["nanos"]=> 0,
                    },
                    ["founder"]=> "funder-did-0001",
                    ["txType"]=> 1,
                }
            ]
        }
    }
}

ix: 未消费交易记录的索引
ctokenId: 交易的数字凭证ID
value: 交易的数量
addr: 交易的目标账户ID
until: until xx timestamp, any one cant spend the txout, -1 means no check
script: 交易的附属数据
createdAt: 交易创建时间
funder: 交易的发起人ID
txType: 交易类型
spentTxDataHash: 已消费交易记录的数据Hash
spentAt: 消费时间
```

## 11.获取秘钥安全码
```code
$did : wallet账户id
$client->tranfserTxn($did,$security_code); 返回值0表示正常
$security_code = "我爱你中国", // 获取秘钥安全码

```

## 12. 获取区块信息
```code
$num : 第几个最新的区块
$client->queryBlockInfo($num,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
	["ErrCode"]=> 0,
	["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> [
		[0] => {
			[Number] => 480
			[channel_id] => "pubchain"
			[current_hash] => "7XK6MDWtceJvcjHphwHjIsLQTGUc69/ia76ewUPZ5+4="
			[previous_hash] => "rUSBBiRHhXLK++3wNEc2yl8bf1Jux4Jnj2T8EvF62uc="
			[timestamp] =>{}
			[transaction_number] => 2
			[transaction_size] => 3766
		}
	]
}

```

## 13.获取交易详细信息
```code
$txn_id : 交易id
$client->getTxnDetail($txn_id,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
	["ErrCode"]=> 0,
	["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> [
		[0] => {
			[channel_id] => "pubchain"
			[blknum] => 480
			[txnid] => "a4477bbf6be6c2f3bc9ca41401c8516f2c1785fcdb9721479dc7df8d11154788"
			[chaincode_id] => "pubchain-utxo:"
			[payload_size] => 1880
			[timestamp] => 1534319154
			[createdat] => "0001-01-01T00:00:00Z"
			[updateat] => "0001-01-01T00:00:00Z"
			[DeletedAt] => NULL
		}
	]
}
```

## 11.错误返回试例
```code
{
    ["ErrCode"]=> 1000,
    ["ErrMessage"]=> "InvalidParamsErrCode",
    ["Method"]=> "",
    ["Payload"]=> NULL,
}
```
以上接口可以满足绝大部分的业务场景，如果以上接口不能满足您的需求，请联系我们的工作人员

# 具体用法请参考test.php
