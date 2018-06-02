unit HttpWrk;

interface

uses
  Classes;

//  function HttpRequest(const AUrl, AData: AnsiString;
//                       AHeader: TStringList;
//                       blnSSL: Boolean = False): AnsiString;

  function ePOST2(URL, _POST: string; AHeader: TStringList): string;
//  function ePOST(URL,_POST:string):string;

implementation

uses
  Winsock, WinInet, SysUtils, Windows;

type
  TStringArray = array of string;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ParseURL(const lpszUrl: string): TStringArray;
var
  lpszScheme      : array[0..INTERNET_MAX_SCHEME_LENGTH - 1] of Char;
  lpszHostName    : array[0..INTERNET_MAX_HOST_NAME_LENGTH - 1] of Char;
  lpszUserName    : array[0..INTERNET_MAX_USER_NAME_LENGTH - 1] of Char;
  lpszPassword    : array[0..INTERNET_MAX_PASSWORD_LENGTH - 1] of Char;
  lpszUrlPath     : array[0..INTERNET_MAX_PATH_LENGTH - 1] of Char;
  lpszExtraInfo   : array[0..1024 - 1] of Char;
  lpUrlComponents : TURLComponents;
begin
  ZeroMemory(@lpszScheme, SizeOf(lpszScheme));
  ZeroMemory(@lpszHostName, SizeOf(lpszHostName));
  ZeroMemory(@lpszUserName, SizeOf(lpszUserName));
  ZeroMemory(@lpszPassword, SizeOf(lpszPassword));
  ZeroMemory(@lpszUrlPath, SizeOf(lpszUrlPath));
  ZeroMemory(@lpszExtraInfo, SizeOf(lpszExtraInfo));
  ZeroMemory(@lpUrlComponents, SizeOf(TURLComponents));

  lpUrlComponents.dwStructSize      := SizeOf(TURLComponents);
  lpUrlComponents.lpszScheme        := lpszScheme;
  lpUrlComponents.dwSchemeLength    := SizeOf(lpszScheme);
  lpUrlComponents.lpszHostName      := lpszHostName;
  lpUrlComponents.dwHostNameLength  := SizeOf(lpszHostName);
  lpUrlComponents.lpszUserName      := lpszUserName;
  lpUrlComponents.dwUserNameLength  := SizeOf(lpszUserName);
  lpUrlComponents.lpszPassword      := lpszPassword;
  lpUrlComponents.dwPasswordLength  := SizeOf(lpszPassword);
  lpUrlComponents.lpszUrlPath       := lpszUrlPath;
  lpUrlComponents.dwUrlPathLength   := SizeOf(lpszUrlPath);
  lpUrlComponents.lpszExtraInfo     := lpszExtraInfo;
  lpUrlComponents.dwExtraInfoLength := SizeOf(lpszExtraInfo);

  InternetCrackUrl(PChar(lpszUrl), Length(lpszUrl), ICU_DECODE or ICU_ESCAPE, lpUrlComponents);

//  Writeln(Format('Protocol : %s',[lpszScheme]));
//  Writeln(Format('Host     : %s',[lpszHostName]));
//  Writeln(Format('User     : %s',[lpszUserName]));
//  Writeln(Format('Password : %s',[lpszPassword]));
//  Writeln(Format('Path     : %s',[lpszUrlPath]));
//  Writeln(Format('ExtraInfo: %s',[lpszExtraInfo]));

  SetLength(Result, 2);
  Result[0] := lpszHostName;
  Result[1] := StrPas(lpszUrlPath) + StrPas(lpszExtraInfo);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}  
function HttpRequest(const AUrl, AData: AnsiString; AHeader: TStringList;
                     blnSSL: Boolean = False): AnsiString;
var
  aBuffer     : Array[0..4096] of Char;
  Header      : TStringStream;
  BufStream   : TMemoryStream;
  sMethod     : AnsiString;
  BytesRead   : Cardinal;
  pSession    : HINTERNET;
  pConnection : HINTERNET;
  pRequest    : HINTERNET;
  parsedURL   : TStringArray;
  port        : Integer;
  flags       : DWord;
  i:          Integer;
begin
  ParsedUrl := ParseUrl(AUrl);
 
  Result := '';
 
  pSession := InternetOpen(nil, INTERNET_OPEN_TYPE_PRECONFIG, nil, nil, 0);
 
  if Assigned(pSession) then
  try
    if blnSSL then
      Port := INTERNET_DEFAULT_HTTPS_PORT
    else
      Port := INTERNET_DEFAULT_HTTP_PORT;
    pConnection := InternetConnect(pSession, PChar(ParsedUrl[0]), port, nil, nil,
                                   INTERNET_SERVICE_HTTP, 0, 0);
 
    if Assigned(pConnection) then
    try
      if (AData = '') then
        sMethod := 'GET'
      else
        sMethod := 'POST';
 
      if blnSSL then
        flags := INTERNET_FLAG_SECURE or INTERNET_FLAG_KEEP_CONNECTION
      else
        flags := INTERNET_SERVICE_HTTP;
 
      pRequest := HTTPOpenRequest(pConnection, PChar(sMethod), PChar(ParsedUrl[1]),
                                  nil, nil, nil, flags, 0);
 
      if Assigned(pRequest) then
      try
        Header := TStringStream.Create('');
        try
          for i := 0 to AHeader.Count - 1 do
          with Header do
          begin
            if i = 0 then
               WriteString(AHeader[i] + ParsedUrl[0] + sLineBreak) else
            if i = AHeader.Count - 1 then
               WriteString(AHeader[i] + SlineBreak + SLineBreak)
            else
               WriteString(AHeader[i] + SlineBreak);

//            WriteString('Host: ' + ParsedUrl[0] + sLineBreak);
//            WriteString('User-Agent: Opera/9.24 (Windows NT 5.1; U; en)'+SLineBreak);
//            WriteString('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'+SLineBreak);
//            WriteString('Accept-Language: en-us,en;q=0.5' + SLineBreak);
//            WriteString('Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7'+SLineBreak);
//            WriteString('Keep-Alive: 300'+ SLineBreak);
//            WriteString('Connection: keep-alive'+ SlineBreak+SLineBreak);
          end;
 
          HttpAddRequestHeaders(pRequest, PChar(Header.DataString), Length(Header.DataString),
                                HTTP_ADDREQ_FLAG_ADD);
 
          if HTTPSendRequest(pRequest, nil, 0, Pointer(AData), Length(AData)) then
          begin
            BufStream := TMemoryStream.Create;
            try
              while InternetReadFile(pRequest, @aBuffer, SizeOf(aBuffer), BytesRead) do
              begin
                if (BytesRead = 0) then Break;
                BufStream.Write(aBuffer, BytesRead);
              end;
 
              aBuffer[0] := #0;
              BufStream.Write(aBuffer, 1);
              Result := PChar(BufStream.Memory);
            finally
              BufStream.Free;
            end;
          end;
        finally
          Header.Free;
        end;
      finally
        InternetCloseHandle(pRequest);
      end;
    finally
      InternetCloseHandle(pConnection);
    end;
  finally
    InternetCloseHandle(pSession);
  end;
end;

function POST(URL, PACKET, host: string):string;
var
 req{,data} : string;
 buf      : array[0..1500] of char;
 wData    : WSADATA;
 addr     : sockaddr_in;
 sock     : integer;
 error    : integer;
 phe      : PHostEnt;
begin
 Result := '';
 WSAStartup($0101, wData);
 phe := gethostbyname(PChar(string(host)));
 if phe = nil then begin
    WSACleanup;
    exit;
 end;
 sock := socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
 if sock = INVALID_SOCKET then begin
    WSACleanup;
    exit;
 end;
 addr.sin_family := AF_INET;
 addr.sin_port   := htons(80);
 addr.sin_addr   := PInAddr(phe.h_addr_list^)^;
 error := connect(sock, addr, sizeof(addr));
 if error = SOCKET_ERROR then begin
    closesocket(sock);
    WSACleanup;
    exit;
 end;
 req := PACKET;
 if Send(Sock,pointer(req)^,length(req),0)=SOCKET_ERROR then exit;
 fillchar(buf,sizeof(buf),0);
 recv(Sock,buf,10000,0);//sizeof(buf
 closesocket(Sock);
 result:=buf;
end;

function ePOST(URL,_POST:string):string;
var req:string;
    host: string;
begin
 host := '95.154.110.154';
 req:='POST '+URL+' HTTP/1.1'#13#10+
      'Host: '+host+#13#10+
      'User-Agent: Opera/9.24 (Windows NT 5.1; U; en)'#13#10+
      'Accept: */*;q=0.1'#13#10+
      'Accept-Encoding: gzip,deflate'#13#10+
      'Accept-Language: ru-RU,ru;q=0.9,en;q=0.8'#13#10+
      'Connection: Keep-Alive'#13#10+
      'Referer: http://vkontakte.ru/index.php'#13#10+
      'Content-Length: '+inttostr(length(_POST))+#13#10+
      'Content-Type: application/x-www-form-urlencoded'#13#10#13#10+_POST;
  result:=POST(URL,req, host);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{ **** UBPFD *********** by delphibase.endimus.com ****
>> URL кодирование строки

Функция производит так назваемое URL кодирование строк для использования
в http запросах. Т.е. все алфавитно-цифровые символы и знак подчёикивания
'_' остаются неизменными, пробел заменяется на '+', а все остальные символы
на знак процента '%' с двумя шестнадцатеричными цифрами.

Зависимости: Windows
Автор:       Dimka Maslov, mainbox@endimus.ru, ICQ:148442121, Санкт-Петербург
Copyright:   Dimka Maslov
Дата:        27 мая 2002 г.
***************************************************** }

function UrlEncode(Str: string): string;

  function CharToHex(Ch: Char): Integer;
  asm
    and eax, 0FFh
    mov ah, al
    shr al, 4
    and ah, 00fh
    cmp al, 00ah
    jl @@10
    sub al, 00ah
    add al, 041h
    jmp @@20
@@10:
    add al, 030h
@@20:
    cmp ah, 00ah
    jl @@30
    sub ah, 00ah
    add ah, 041h
    jmp @@40
@@30:
    add ah, 030h
@@40:
    shl eax, 8
    mov al, '%'
  end;

var
  i, Len: Integer;
  Ch: Char;
  N: Integer;
  P: PChar;
begin
  Result := '';
  Len := Length(Str);
  P := PChar(@N);
  for i := 1 to Len do
  begin
    Ch := Str[i];
    if Ch in ['0'..'9', 'A'..'Z', 'a'..'z', '_'] then
      Result := Result + Ch
    else
    begin
      if Ch = ' ' then
        Result := Result + '+'
      else
      begin
        N := CharToHex(Ch);
        Result := Result + P;
      end;
    end;
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function ePOST2(URL, _POST: string; AHeader: TStringList): string;
var
  req: string;
  ParsedUrl: TStringArray;
  i: Integer;
begin
  req := '';

  ParsedUrl := ParseUrl(URL);

  for i := 0 to AHeader.Count - 1 do
  with AHeader do begin
    if i = 0 then
       req := req + Format(AHeader[i], [ParsedUrl[1]]) +  sLineBreak else
    if i = 1 then
       req := req + Format(AHeader[i], [ParsedUrl[0]]) + sLineBreak else
    if i = AHeader.Count - 2 then
       req := req + Format(AHeader[i], [Length(_POST)]) + sLineBreak else
    if i = AHeader.Count - 1 then
       req := req + AHeader[i] + sLineBreak + sLineBreak + _POST
    else
       req := req + AHeader[i] + sLineBreak;
  end;
  Result := POST(URL, req, ParsedUrl[0]);
end;

end.
