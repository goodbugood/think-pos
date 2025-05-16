# 力 pos 对接

## 注意

1. 商户绑定的 pos，不允许平台再修改终端费率
2. 已经解绑的 pos，不允许再次解绑，只能绑定后再解绑
3. 流量套餐和押金套餐要先查询终端获得数据，再修改
4. 支付类型必须全部接入，云闪付 1000- 可以用微信支付宝的，特惠那四个可以用贷记卡的
5. 加密使用的是对称加密，密钥自己同步生成，并上传

## 商户

### 设置商户费率

1. 修改商户费率失败：code=98&msg=贷记卡费率的值10.000000必须在0.51到0.72之间
2. pos服务商[力POS]修改商户费率失败：code=98&msg=借记卡费率的值10.000000必须在0.51到0.72之间
3. code=98&msg=借记卡封顶值1000.00必须在18.00到25.00之间

## pos 终端相

### 设置 pos 费率

1. 已绑定机器不允许修改费率

### 查询 pos 信息

- 失败响应

```json
{
    "code": "98",
    "msg": "终端信息不存在",
    "success": false
}
```

- 响应成功

```json
{
    "appId": "61261936",
    "code": "00",
    "data": "d3HVQWxPTQIRNxce9oXU3ORrbp4DRaSYCIqQXQ0Mg8zMtO5FpOTnRRfSIzdPF4xIT8HQ8YItHmG8xXp9Oy5dTOHCVC/+wyDMxybSJUXxhsccQWkMHTnyN0XqyuupC3ivSW3da6RI2ET1ouF+CA43lfQf5eGt1qpxm9oI0rTvUv48WjMK5ojXjGub3VL/jMC4ld3RHUGjQMpMjuCi0Cri/NqI+j77YBMqmz3vxhe9cWiSjyxYSNSwyaG6wCdXcPzXStLKeAcG69EEnyql0TqygNk5RCFVbt2n6196wAdK5OFNxpKpFX5AyoE58dTrlLNYTXTNEgtrdbL6ieMQ979FfL/hE7tYPCK6QP4cAGxJ4ThKuq41AtasXhhTjkl/dVYoxK3t83O27cJn5CFSpzVc3ZUmFTVmS8T3xmIpHtqYykIV9iZfQuhpcE1XWhpSkjnVaQr5aVJVU+efzdrWvsM74DRsfBCZ4sld1w+eHqUT0F2iaGce8Sh6o3pcnAqfB6wDNd2P6UlWOzWJIw2nyNWUpIZ15y0gjNxj/WzzM2R7AY6IYO+vWf4crSkiHwQFHfKdROFK4O4YATIIUyzoWBBmEytwklRB0VLth/CrrcHrpCfaiOhKNzvBShtbmwIfxpDa0mP1dGnR0xT0cb17KCHFIqE/7RNb/LTvGshKews6EkTcdyoPh0D4GqDQMd4Reigwh+LRxNXcY96ElGTE6ugfiOScvgFIZoKeym4igVYPFimK8DFOMJUc7mQRs8nV3M1fVAOGAALUVQImWzftldl31Sofz2IfViPYBfIrD38burJ5ubpbn63MUtjtrhbWh0SpRlMLu3l+Akz//rC+cN4KBUioi7mUIkKUAGfe/9Kk1cIVMiHXG9zLX5t2+DFbmQjwtf/EXB6KMpZpfV3WBizyUHr8MfgGvhW2Wwbs3Nr1bKeC7ihksEBfqy8SUX1GLYSHsD6XaWtwQ7YksoidKrqqkzDbqbpKrabNx05yOozG0iezLT/e6azjEsk8Cq4u1MBTP14o1itKNlWI3PYzZdHJw41PZF4j9vDXbtooqMRnjCJuNkojZJCJPxBNr+VtjYaIZezZxysoEHOm916H80YO5LA89cqXaisknyVq18nHLn2i+V3zmugJc4aM+ROTqw8eQwTq4ruW3Lx+xgLRTUSoM7mhoakCexFZpJMu3QqLHCzWaM/ygyF8Dy0ImNIrun9sZezZxysoEHOm916H80YO5LA89cqXaisknyVq18nHLn2i+V3zmugJc4aM+ROTqw8eQwTq4ruW3Lx+xgLRTUSoMx1M+GguZ+L53uboxN1c6gl3dkWTQJHNVWRnZ50KwkTjZezZxysoEHOm916H80YO5LA89cqXaisknyVq18nHLn2i+V3zmugJc4aM+ROTqw8eQwTq4ruW3Lx+xgLRTUSoMyCC2H5HDVaEJ3XhbgAHy1MJGmtt3YSHVe2zdlG+XqADSKiLuZQiQpQAZ97/0qTVwhUyIdcb3Mtfm3b4MVuZCPC1/8RcHooylml9XdYGLPJQaRCRpCc+A1F4aP0mcS7NlP/isI4ja89mj+1GiQSWYMv8ZiMpC/n9EvRCYe9IRrJR45uCLsYnkDiVlqTeSzhevmhglVnzqZ44IMetT0GmKIQSZEdLdbrLfDjHsUO8/zGWbCYRPdYiDC/8TKJ0hTT+WA==",
    "encryptKey": "oI6YkvYsPG2CAK2DcGh4yil7LEHpdwlDqOBNOOvaE8E5yCDICCwAQyL9lWvilKw+M2czcLm9sYBnqZSWzMd9cstXy1YEytB7HmEkWB+NQNIbmTQi4EHHGioMnkeaVNYDTeJbu8ulGbLw2I8mpRwa3G1Htxob6HlH7HTcawS02dcRUVdNQ24Mnpp9s0mMacLKMUWHMsSuE6kYtjdD5HPW34Ln/P8TdXkhkmpg1ewYuKmsHQz55l1XIwkzJbkOekkfK2V4ln8hsH3n5+HHIIGjVzvxlHHH59QSpIyXZjp7axVLN/g/SRHIpH83+vk9KkbgI7cp3/pTKVYSsuEAojZgpA==",
    "msg": "成功",
    "responseId": "JZY0815093727234",
    "sign": "OJXh+S8itEfswr2+yQS+skYGog60uCqVa7x+VqkN5wnLQbIaYEcxv2O+OsVvmsvJObGn8KkY4wZKV1D8r7d+Pn2OF9+jLREUWb2vANeZrnMUguwhfACu54+oNyT2zNk11Cut33433S8jSfyCjQeEhblbGr67l1biGuXiNI4vX0z5I1tvejHzedjKBHMW8PRuGy/II8vPmqZKVP7WZvKEHcMorFLHAwB3SQ5d+6nzfvO9bULHqoeep4T+EX5AxC1ZtVmOcVSjHzQeoPwWmrNsbTHEHMm5uT5Ye+Rs6lBRRuUa0bLYlS1jti1u/K30KG4sJ7d+4kruNxJOF8bEf5UcMA==",
    "success": true,
    "timestamp": "1746689403693"
}
```

- 解密后的数据

未绑定商户的 pos 返回的携带费率信息

```json
{"factoryInfo":"MOREFUN","materialsMachineInfo":{"machineDeductionStatus":"NO","materialsMachineList":[{"enableStatus":"NO","machineAmount":0.00,"machinePhaseIndex":0},{"enableStatus":"YES","machineAmount":199.00,"machinePhaseIndex":1},{"enableStatus":"NO","machineAmount":299.00,"machinePhaseIndex":2}]},"materialsModel":"DQ72","materialsNo":"000031000000000000023","materialsRateList":[{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"WECHAT","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"ALIPAY","rateValue":0.66},{"cappingValue":20.00,"fixedValue":0.00,"payTypeViewCode":"POS_DC","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"POS_CC","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"POS_DISCOUNT_CC","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"POS_DISCOUNT_GF_CC","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"POS_DISCOUNT_PA_CC","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"POS_DISCOUNT_MS_CC","rateValue":0.66},{"cappingValue":0.00,"fixedValue":0.00,"payTypeViewCode":"UNIONPAY_DOWN_CC","rateValue":0.66}],"materialsSimInfo":{"dateComputeType":"AFTER_BINDING","simFreeDay":0,"simPhaseList":[{"beginDayMaxRang":7,"beginDayMinRang":1,"beginDayNum":1,"deductionStatus":"NO","endDayNum":60,"simDeductionsList":[{"deductionAmount":19.00,"enableStatus":"YES","simPhaseIndex":19},{"deductionAmount":29.00,"enableStatus":"NO","simPhaseIndex":29},{"deductionAmount":39.00,"enableStatus":"NO","simPhaseIndex":39}],"simRuleIndex":1}]},"materialsType":"DQPOS","policyId":1920297486543163393}
```

如果是绑定了商户的 pos 查询，不会返回费率

```json
{"factoryInfo":"MOREFUN","materialsMachineInfo":{"machineDeductionStatus":"NO","materialsMachineList":[{"enableStatus":"NO","machineAmount":0.00,"machinePhaseIndex":0},{"enableStatus":"YES","machineAmount":199.00,"machinePhaseIndex":1},{"enableStatus":"NO","machineAmount":299.00,"machinePhaseIndex":2}]},"materialsModel":"DQ72","materialsNo":"000031000000000000023","materialsSimInfo":{"dateComputeType":"AFTER_BINDING","simFreeDay":0,"simPhaseList":[{"beginDayMaxRang":7,"beginDayMinRang":1,"beginDayNum":1,"deductionStatus":"NO","endDayNum":60,"simDeductionsList":[{"deductionAmount":19.00,"enableStatus":"YES","simPhaseIndex":19},{"deductionAmount":29.00,"enableStatus":"NO","simPhaseIndex":29},{"deductionAmount":39.00,"enableStatus":"NO","simPhaseIndex":39}],"simRuleIndex":1}]},"materialsType":"DQPOS","policyId":1920297486543163393}
```