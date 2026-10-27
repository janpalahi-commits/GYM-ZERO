// GYM-ZERO · Simulador del torn d'entrada a les sales
//
// Fa el que faria el torn real (dissenyat però no construït):
//   1. El client acosta la polsera RFID al lector del torn (aquí s'escriu el codi de la polsera).
//   2. El torn pregunta a l'API PHP si pot passar.
//   3. Si l'API diu que sí, desbloqueja el torn per a una volta; si diu que no, ensenya el motiu.
//
// Compilar:  g++ -std=c++17 -Wall -o torn torn.cpp
// Executar:  ./torn [url_api] [codi_torn] [clau]
//   ex.:     ./torn http://localhost/gymzero/api/entrada.php TORN-01 token-TORN-01

#include <iostream>
#include <string>

#include "api.h"

using namespace std;

int main(int argc, char* argv[])
{
    string url  = argc > 1 ? argv[1] : "http://localhost/gymzero/api/entrada.php";
    string torn = argc > 2 ? argv[2] : "TORN-01";
    string clau = argc > 3 ? argv[3] : "token-TORN-01";

    cout << "=== GYM-ZERO · Simulador del torn " << torn << " ===\n";
    cout << "API: " << url << "\n";
    cout << "Polseres de prova: 04A1B2C3 (Marc), 04D4E5F6 (Laia), 04078899 (Pau), AD000001 (Admin)\n";

    while (true) {
        string targeta;
        cout << "\nAcosta la polsera (escriu el codi, o 'q' per sortir): ";
        if (!(cin >> targeta) || targeta == "q") {
            break;
        }

        string json = "{\"torn\":\"" + torn + "\",\"targeta\":\"" + targeta + "\"}";
        string resposta;
        long codi = envia(url, clau, json, resposta);

        if (codi == 0) {
            // Sense connexió el torn no pot saber si la quota està pagada: queda bloquejat
            cout << "[TORN BLOQUEJAT] Sense connexió. Passa per recepció (" << resposta << ")\n";
        } else if (codi == 200) {
            bool obrir = camp(resposta, "obrir") == "true";
            cout << (obrir ? "[TORN DESBLOQUEJAT] " : "[TORN BLOQUEJAT] ") << camp(resposta, "missatge") << "\n";
            string avis = camp(resposta, "avis");
            if (!avis.empty()) {
                cout << "                    " << avis << "\n";
            }
        } else {
            cout << "Error " << codi << ": " << camp(resposta, "error") << "\n";
        }
    }

    cout << "Adéu!\n";
    return 0;
}
