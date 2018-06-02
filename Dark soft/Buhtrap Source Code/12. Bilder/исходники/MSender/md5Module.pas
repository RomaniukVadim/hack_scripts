unit md5Module;

interface
{$HINTS OFF}

uses
  Windows, SysUtils;


function md5(S: String): String;

implementation

type
  TArrayOfByte = Array of Byte;
  TArrayOfDWORD = Array of DWORD;

const
  HashSize = 16;
  BuffSize = 64;


function LRot32(A: DWORD; B: Byte): DWORD;
begin
  Result:= (A shl B) or (A shr (32-B));
end;

procedure Compressor(Hash, Buffer: Pointer);
var
  A, B, C, D: DWORD;
begin
  A := TArrayOfDWORD(Hash)[0];
  B := TArrayOfDWORD(Hash)[1];
  C := TArrayOfDWORD(Hash)[2];
  D := TArrayOfDWORD(Hash)[3];
  //
  A := B + LRot32(A + (D xor (B and (C xor D))) + TArrayOfDWORD(Buffer)[ 0] + $D76AA478,  7);
  D := A + LRot32(D + (C xor (A and (B xor C))) + TArrayOfDWORD(Buffer)[ 1] + $E8C7B756, 12);
  C := D + LRot32(C + (B xor (D and (A xor B))) + TArrayOfDWORD(Buffer)[ 2] + $242070DB, 17);
  B := C + LRot32(B + (A xor (C and (D xor A))) + TArrayOfDWORD(Buffer)[ 3] + $C1BDCEEE, 22);
  A := B + LRot32(A + (D xor (B and (C xor D))) + TArrayOfDWORD(Buffer)[ 4] + $F57C0FAF,  7);
  D := A + LRot32(D + (C xor (A and (B xor C))) + TArrayOfDWORD(Buffer)[ 5] + $4787C62A, 12);
  C := D + LRot32(C + (B xor (D and (A xor B))) + TArrayOfDWORD(Buffer)[ 6] + $A8304613, 17);
  B := C + LRot32(B + (A xor (C and (D xor A))) + TArrayOfDWORD(Buffer)[ 7] + $FD469501, 22);
  A := B + LRot32(A + (D xor (B and (C xor D))) + TArrayOfDWORD(Buffer)[ 8] + $698098D8,  7);
  D := A + LRot32(D + (C xor (A and (B xor C))) + TArrayOfDWORD(Buffer)[ 9] + $8B44F7AF, 12);
  C := D + LRot32(C + (B xor (D and (A xor B))) + TArrayOfDWORD(Buffer)[10] + $FFFF5BB1, 17);
  B := C + LRot32(B + (A xor (C and (D xor A))) + TArrayOfDWORD(Buffer)[11] + $895CD7BE, 22);
  A := B + LRot32(A + (D xor (B and (C xor D))) + TArrayOfDWORD(Buffer)[12] + $6B901122,  7);
  D := A + LRot32(D + (C xor (A and (B xor C))) + TArrayOfDWORD(Buffer)[13] + $FD987193, 12);
  C := D + LRot32(C + (B xor (D and (A xor B))) + TArrayOfDWORD(Buffer)[14] + $A679438E, 17);
  B := C + LRot32(B + (A xor (C and (D xor A))) + TArrayOfDWORD(Buffer)[15] + $49B40821, 22);

  A := B + LRot32(A + (C xor (D and (B xor C))) + TArrayOfDWORD(Buffer)[ 1] + $F61E2562,  5);
  D := A + LRot32(D + (B xor (C and (A xor B))) + TArrayOfDWORD(Buffer)[ 6] + $C040B340,  9);
  C := D + LRot32(C + (A xor (B and (D xor A))) + TArrayOfDWORD(Buffer)[11] + $265E5A51, 14);
  B := C + LRot32(B + (D xor (A and (C xor D))) + TArrayOfDWORD(Buffer)[ 0] + $E9B6C7AA, 20);
  A := B + LRot32(A + (C xor (D and (B xor C))) + TArrayOfDWORD(Buffer)[ 5] + $D62F105D,  5);
  D := A + LRot32(D + (B xor (C and (A xor B))) + TArrayOfDWORD(Buffer)[10] + $02441453,  9);
  C := D + LRot32(C + (A xor (B and (D xor A))) + TArrayOfDWORD(Buffer)[15] + $D8A1E681, 14);
  B := C + LRot32(B + (D xor (A and (C xor D))) + TArrayOfDWORD(Buffer)[ 4] + $E7D3FBC8, 20);
  A := B + LRot32(A + (C xor (D and (B xor C))) + TArrayOfDWORD(Buffer)[ 9] + $21E1CDE6,  5);
  D := A + LRot32(D + (B xor (C and (A xor B))) + TArrayOfDWORD(Buffer)[14] + $C33707D6,  9);
  C := D + LRot32(C + (A xor (B and (D xor A))) + TArrayOfDWORD(Buffer)[ 3] + $F4D50D87, 14);
  B := C + LRot32(B + (D xor (A and (C xor D))) + TArrayOfDWORD(Buffer)[ 8] + $455A14ED, 20);
  A := B + LRot32(A + (C xor (D and (B xor C))) + TArrayOfDWORD(Buffer)[13] + $A9E3E905,  5);
  D := A + LRot32(D + (B xor (C and (A xor B))) + TArrayOfDWORD(Buffer)[ 2] + $FCEFA3F8,  9);
  C := D + LRot32(C + (A xor (B and (D xor A))) + TArrayOfDWORD(Buffer)[ 7] + $676F02D9, 14);
  B := C + LRot32(B + (D xor (A and (C xor D))) + TArrayOfDWORD(Buffer)[12] + $8D2A4C8A, 20);

  A := B + LRot32(A + (B xor C xor D) + TArrayOfDWORD(Buffer)[ 5] + $FFFA3942,  4);
  D := A + LRot32(D + (A xor B xor C) + TArrayOfDWORD(Buffer)[ 8] + $8771f681, 11);
  C := D + LRot32(C + (D xor A xor B) + TArrayOfDWORD(Buffer)[11] + $6D9D6122, 16);
  B := C + LRot32(B + (C xor D xor A) + TArrayOfDWORD(Buffer)[14] + $FDE5380C, 23);
  A := B + LRot32(A + (B xor C xor D) + TArrayOfDWORD(Buffer)[ 1] + $A4BEEA44,  4);
  D := A + LRot32(D + (A xor B xor C) + TArrayOfDWORD(Buffer)[ 4] + $4BDECFA9, 11);
  C := D + LRot32(C + (D xor A xor B) + TArrayOfDWORD(Buffer)[ 7] + $F6BB4B60, 16);
  B := C + LRot32(B + (C xor D xor A) + TArrayOfDWORD(Buffer)[10] + $BEBFBC70, 23);
  A := B + LRot32(A + (B xor C xor D) + TArrayOfDWORD(Buffer)[13] + $289B7EC6,  4);
  D := A + LRot32(D + (A xor B xor C) + TArrayOfDWORD(Buffer)[ 0] + $EAA127FA, 11);
  C := D + LRot32(C + (D xor A xor B) + TArrayOfDWORD(Buffer)[ 3] + $D4EF3085, 16);
  B := C + LRot32(B + (C xor D xor A) + TArrayOfDWORD(Buffer)[ 6] + $04881D05, 23);
  A := B + LRot32(A + (B xor C xor D) + TArrayOfDWORD(Buffer)[ 9] + $D9D4D039,  4);
  D := A + LRot32(D + (A xor B xor C) + TArrayOfDWORD(Buffer)[12] + $E6DB99E5, 11);
  C := D + LRot32(C + (D xor A xor B) + TArrayOfDWORD(Buffer)[15] + $1FA27CF8, 16);
  B := C + LRot32(B + (C xor D xor A) + TArrayOfDWORD(Buffer)[ 2] + $C4AC5665, 23);

  A := B + LRot32(A + (C xor (B or (not D))) + TArrayOfDWORD(Buffer)[ 0] + $F4292244,  6);
  D := A + LRot32(D + (B xor (A or (not C))) + TArrayOfDWORD(Buffer)[ 7] + $432AFF97, 10);
  C := D + LRot32(C + (A xor (D or (not B))) + TArrayOfDWORD(Buffer)[14] + $AB9423A7, 15);
  B := C + LRot32(B + (D xor (C or (not A))) + TArrayOfDWORD(Buffer)[ 5] + $FC93A039, 21);
  A := B + LRot32(A + (C xor (B or (not D))) + TArrayOfDWORD(Buffer)[12] + $655B59C3,  6);
  D := A + LRot32(D + (B xor (A or (not C))) + TArrayOfDWORD(Buffer)[ 3] + $8F0CCC92, 10);
  C := D + LRot32(C + (A xor (D or (not B))) + TArrayOfDWORD(Buffer)[10] + $FFEFF47D, 15);
  B := C + LRot32(B + (D xor (C or (not A))) + TArrayOfDWORD(Buffer)[ 1] + $85845DD1, 21);
  A := B + LRot32(A + (C xor (B or (not D))) + TArrayOfDWORD(Buffer)[ 8] + $6FA87E4F,  6);
  D := A + LRot32(D + (B xor (A or (not C))) + TArrayOfDWORD(Buffer)[15] + $FE2CE6E0, 10);
  C := D + LRot32(C + (A xor (D or (not B))) + TArrayOfDWORD(Buffer)[ 6] + $A3014314, 15);
  B := C + LRot32(B + (D xor (C or (not A))) + TArrayOfDWORD(Buffer)[13] + $4E0811A1, 21);
  A := B + LRot32(A + (C xor (B or (not D))) + TArrayOfDWORD(Buffer)[ 4] + $F7537E82,  6);
  D := A + LRot32(D + (B xor (A or (not C))) + TArrayOfDWORD(Buffer)[11] + $BD3AF235, 10);
  C := D + LRot32(C + (A xor (D or (not B))) + TArrayOfDWORD(Buffer)[ 2] + $2AD7D2BB, 15);
  B := C + LRot32(B + (D xor (C or (not A))) + TArrayOfDWORD(Buffer)[ 9] + $EB86D391, 21);
  //
  Inc(TArrayOfDWORD(Hash)[0], A);
  Inc(TArrayOfDWORD(Hash)[1], B);
  Inc(TArrayOfDWORD(Hash)[2], C);
  Inc(TArrayOfDWORD(Hash)[3], D);
end;

function HashToStr(Hash: Pointer): String;
var
  i: Byte;
begin
  Result := '';
  for i := 0 to HashSize - 1 do
    Result := Result + IntToHex(TArrayOfByte(Hash)[i], 2);
end;

function md5(S: String): String;
var
  PBuff: PChar;
  CurrentHash, HashBuffer: Pointer;
  Size: LongWord;
  LenLO, LenHI: LongWord;
begin
  Result := '';
  CurrentHash := GetMemory(HashSize);
  HashBuffer := GetMemory(BuffSize);
  if (CurrentHash = nil) or (HashBuffer = nil) then Raise Exception.Create('System error!'+#13#10+'Can not get memory!');
  try
    Size := Length(S);
    LenLO := Size * 8;
    LenHI := Size shr 29;
    S := S + #$80;
    Inc(Size);
    PBuff := PChar(S);
    //
    TArrayOfDWORD(CurrentHash)[0] := $67452301;
    TArrayOfDWORD(CurrentHash)[1] := $EFCDAB89;
    TArrayOfDWORD(CurrentHash)[2] := $98BADCFE;
    TArrayOfDWORD(CurrentHash)[3] := $10325476;
    ZeroMemory(HashBuffer, BuffSize);
    //
    While (Size >= BuffSize) do
                               begin
                                 CopyMemory(HashBuffer, PBuff, BuffSize);
                                 Compressor(CurrentHash, HashBuffer);
                                 ZeroMemory(HashBuffer, BuffSize);
                                 Inc(PBuff, BuffSize);
                                 Dec(Size, BuffSize);
                               end;
    if (Size > 0) then
                      begin
                        CopyMemory(HashBuffer, PBuff, Size);
                        Inc(PBuff, Size);
                      end;
    //
    if (Size > 56) then
                       begin
                         Compressor(CurrentHash, HashBuffer);
                         ZeroMemory(HashBuffer, BuffSize);
                       end;
    TArrayOfDWORD(HashBuffer)[14] := LenLO;
    TArrayOfDWORD(HashBuffer)[15] := LenHI;
    Compressor(CurrentHash, HashBuffer);
    ZeroMemory(HashBuffer, BuffSize);
    //
    Result := LowerCase(HashToStr(CurrentHash));
  finally
    FreeMemory(CurrentHash);
    FreeMemory(HashBuffer);
  end;
end;

{$HINTS ON}
end.