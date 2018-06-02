unit RegWrk;

interface

uses
  Windows;

  function WriteXmlNodeToReg(const ARootKey: DWORD; RegKey, ValName, BinValue: string): Boolean;

  function GetProxyServer: string;

implementation

uses
  Registry, Crypto;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function WriteXmlNodeToReg(const ARootKey: DWORD; RegKey, ValName, BinValue: string): Boolean;
begin
  Result := False;

  try
    with TRegIniFile.Create(KEY_WRITE) do
      try
        RootKey := ARootKey;
        if OpenKey(RegKey, True) then
          begin
            WriteBinaryData(ValName, BinValue[1], Length(BinValue));
            CloseKey;
            Result := True;
          end;
      finally
        Free;
      end;
  except
   //
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GetProxyServer: string;
const
  INET_SETTINGS = {#CRYPT 'Software\Microsoft\Windows\CurrentVersion\Internet Settings'}#46#247#159#56#191#41#2#8#144#68#198#140#119#145#220#51#50#157#167#236#227#238#178#181#210#173#225#169#60#40#16#26#140#58#249#139#21#226#77#67#79#118#181#226#214#37#250#252#243#185#118#216#208#54#107#44#27#100#220#54#184{ENDC};
  PROXY_ENABLE = {#CRYPT 'ProxyEnable'}#46#244#32#26#124#228#183#201#69#77#228#224#186{ENDC};
  PROXY_SERVER = {#CRYPT 'ProxyServer'}#46#244#32#26#124#228#161#16#71#231#107#215#26{ENDC};
var
  Registry: TRegistry;
begin
  Result := '';
  try
    Registry := TRegIniFile.Create(KEY_READ);
    with Registry do
      try
        RootKey := HKEY_CURRENT_USER;
        if OpenKey(DecStr(INET_SETTINGS), True) then
          begin
            if ValueExists(DecStr(PROXY_ENABLE)) then
              if ReadInteger(DecStr(PROXY_ENABLE)) = 1 then
                 Result := ReadString(DecStr(PROXY_SERVER));
            CloseKey;
          end;
      finally
        Free;
      end;
  except
   //
  end;
end;


end.
