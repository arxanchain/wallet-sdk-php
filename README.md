# wallet-sdk-php
 Blockchain Wallet SDK includes APIs for managing wallet accounts(Decentralized Identifiers, DID), digital assets(Proof of Existence, POE), colored tokens etc.  You need not care about how the backend blockchain runs or the unintelligible techniques, such as consensus, endorsement and decentralization. Simply use the SDK we provide to implement your business logics, we will handle the caching, tagging, compressing, encrypting and high availability.

# 使用说明

## 1. 注册wallet客户端
```code
$host:arxan-chain wallet服务的ip与port
$api_key:注册企业账户返回的api=key
$cert_path:秘钥与证书目录
$did:企业wallet账户id
$client = new WalletClient($host,$api_key,$cert_path,$did);
```

## 2. 注册wallet账户
```code
$register = array(
    "type"=> "Organization", //类型(必填)
    "access"=> "songtest22", //账户名
    "phone"=> "18337177372", //电话
    "email"=> "Tom@163.com", //邮箱
    "secret"=> "SONGsong110", //账户密码(必填)
);

$ret = $client->register($register,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["code"]=> 0,
        ["message"]=> "",
        ["id"]=> "did:axn:53915ff7-16b4-432e-b3f4-8d3cb44b5240", //分配给钱包的唯一ID
        ["endpoint"]=> "06aa7f0690ef573a9bde61d312ff54036e267e45e856571215e043862d788058", //分配给钱包的地址
        ["key_pair"]=> {
            ["private_key"]=> "Ob6a9aPHBNb5svU4CNSja3exzxDGXiXMERFrO584VSB1k/kvqQvqe6+6pX3DNW2/XH9Ak5YlDVxnH76fIVjOpQ==",
            ["public_key"]=> "dZP5L6kL6nuvuqV9wzVtv1x/QJOWJQ1cZx++nyFYzqU=",
        }
        ["created"]=> 1529906262, //创建钱包的时间戳
        ["token_id"]=> "",
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
$poe = array(
    "id"=> "",
    "name"=> "测试1",(必填)
    "parent_id"=> "parent-poe-id",
    "hash"=> "metadata-hash",
    "owner"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填)
    "metadata"=> "GhaHjjdmN2VkNGU5M2NhMzk1MmM4NDgzZGNlN2Y4YTExZmRmOTEyNmU2ZTU2NWMzNzk3MTA1NjkzMWRiMjBkZjEy",
);

$signature = array(
    "did"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填) //owner did
    "nonce"=> "nonce",(必填) //随机数
    "key"=> "Ob6a9aPHBNb5svU4CNSja3exzxDGXiXMERFrO584VSB1k/kvqQvqe6+6pX3DNW2/XH9Ak5YlDVxnH76fIVjOpQ==",(必填) //owner private_key
);

$ret = $client->createPOE($poe,$signature,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["code"]=> 0,
        ["message"]=> "",
        ["id"]=> "did:axn:c9f0a2a0-8428-49da-b606-28c66baa1423", //资产ID
        ["endpoint"]=> "", 
        ["key_pair"]=> {},
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
$ctoken = array(
    "issuer"=> "did:axn:21tDAKCERh95uGgKbJNHYp",(必填) //发行者
    "owner"=> "did:axn:65tGAKCERh95uHllllllRU",(必填) //拥有者 
    "asset_id"=> "did:axn:90tGAKCERh95uHhhsdljRU",(必填) // 资产id
    "amount"=> 1000,(必填) //数量
    "fee": {
        "amount": 10
    }
)

$signature = array(
    "did"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填) //owner did
    "nonce"=> "nonce",(必填) //随机数
    "key"=> "Ob6a9aPHBNb5svU4CNSja3exzxDGXiXMERFrO584VSB1k/kvqQvqe6+6pX3DNW2/XH9Ak5YlDVxnH76fIVjOpQ==",(必填) //owner private_key
);

$ret = $client->issuerCToken($token,$signature,$token_res); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["code"]=> 0,
        ["message"]=> "",
        ["id"]=> "did:axn:c9f0a2a0-8428-49da-b606-28c66baa1423", //资产ID
        ["endpoint"]=> "", 
        ["key_pair"]=> {},
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
$asset = array(
    "issuer": "did:axn:21tDAKCERh95uGgKbJNHYp",(必填)
    "owner": "did:axn:65tGAKCERh95uHllllllRU",(必填)
    "asset_id": "did:axn:90tGAKCERh95uHhhsdljRU",(必填)
    "fee": {
        "amount": 10
    }
)
$signature = array(
    "did"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填) //owner did
    "nonce"=> "nonce",(必填) //随机数
    "key"=> "Ob6a9aPHBNb5svU4CNSja3exzxDGXiXMERFrO584VSB1k/kvqQvqe6+6pX3DNW2/XH9Ak5YlDVxnH76fIVjOpQ==",(必填) //owner private_key
);
$ret = $client->issuerAsset($asset,$signature,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["code"]=> 0,
        ["message"]=> "",
        ["id"]=> "", 
        ["endpoint"]=> "", 
        ["key_pair"]=> {},
        ["created"]=> 1529906262, //创建资产的时间戳
        ["token_id"]=> "", 
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",
    }
}
```

## 6.转让资产
```code
$data = array(
    "from"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填)
    "to"=> "did:axn:21tDAKCERh95uGgKbJNHYp",(必填)
    "assets"=> [
        "did:axn:c9f0a2a0-8428-49da-b606-28c66baa1423"(必填) //poe资产id
    ]
)
$signature = array(
    "did"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填) //issuer did
    "nonce"=> "nonce",(必填) //随机数
    "key"=> "Ob6a9aPHBNb5svU4CNSja3exzxDGXiXMERFrO584VSB1k/kvqQvqe6+6pX3DNW2/XH9Ak5YlDVxnH76fIVjOpQ==",(必填) //issuer private_key
);
$ret = $client->transferAsset($data,$signature,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["code"]=> 0,
        ["message"]=> "",
        ["id"]=> "", 
        ["endpoint"]=> "", 
        ["key_pair"]=> {},
        ["created"]=> 0,
        ["token_id"]=> "", 
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",//区块链hash
    }
}
```

## 7.转让数字凭证
```code
$data = array(
    "from"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填)
    "to"=> "did:axn:21tDAKCERh95uGgKbJNHYp",(必填)
    "tokens"=> [
        {
            "token_id": "1f38a7a1-2c79-465e-a4c0-0038e25c7edg",(必填)issuerCToken 返回的token_id;
            "amount": 5,(必填)
        }
    ],
)
$signature = array(
    "did"=> "did:axn:8uQhQMGzWxR8vw5P3UWH1j",(必填) //issuer did
    "nonce"=> "nonce",(必填) //随机数
    "key"=> "Ob6a9aPHBNb5svU4CNSja3exzxDGXiXMERFrO584VSB1k/kvqQvqe6+6pX3DNW2/XH9Ak5YlDVxnH76fIVjOpQ==",(必填) //issuer private_key
);
$ret = $client->transferCToken($data,$signature,$response); 返回值0表示正常
$response 为请求返回的多维数组
{
    ["ErrCode"]=> 0,
    ["ErrMessage"]=> "",
    ["Method"]=> "",
    ["Payload"]=> {
        ["code"]=> 0,
        ["message"]=> "",
        ["id"]=> "", 
        ["endpoint"]=> "", 
        ["key_pair"]=> {},
        ["created"]=> 0, //创建资产的时间戳
        ["token_id"]=> "", 发行数字资产id，用于交易
        ["transaction_ids"]=> {
            "34b847f2f16152cdb49f122c77403e6d90890c7b5e688b962227aaa20604546c",//区块链hash
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
        ["code"]=> 0,
        ["message"]=> "",
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

## 错误返回试例
```code
{
    ["ErrCode"]=> 1000,
    ["ErrMessage"]=> "InvalidParamsErrCode",
    ["Method"]=> "",
    ["Payload"]=> NULL,
}
```
以上接口可以满足绝大部分的业务场景，如果以上接口不能满足您的需求，可以调用我们的高级接口，若还不能满足，请联系我们的工作人员

# 具体用法请参考test.php
