// GYM-ZERO · Simulador de màquina intel·ligent
//
// Fa el que faria la màquina real (dissenyada però no construïda):
//   1. L'usuari acosta la polsera RFID al lector (aquí s'escriu el codi de la polsera).
//   2. Fa una sèrie: el simulador inventa les dades de cada repetició
//      (velocitat, recorregut i durada), com les mesuraria el sensor.
//   3. Envia la sèrie a l'API PHP en JSON i ensenya la resposta.
//      Per enviar-la fa servir el programa curl (ve amb Ubuntu), així no cal
//      instal·lar cap llibreria per compilar.
//
// Compilar:  g++ -std=c++17 -Wall -o simulador simulador.cpp
// Executar:  ./simulador [url_api] [codi_maquina] [clau]
//   ex.:     ./simulador http://localhost/gymzero/api/serie.php PRESS-01 token-PRESS-01

#include <cstdio>
#include <cstdlib>
#include <fstream>
#include <iostream>
#include <random>
#include <sstream>
#include <string>
#include <vector>

#include "api.h"

using namespace std;

struct Repeticio {
    double velocitat;   // m/s
    double recorregut;  // m
    double durada;      // s
};

mt19937 generador(random_device{}());

double aleatori(double min, double max)
{
    uniform_real_distribution<double> d(min, max);
    return d(generador);
}

// Genera les repeticions d'una sèrie.
// Com més pes, més lenta va la barra; i a cada repetició es perd una mica
// de velocitat pel cansament (entre un 2% i un 5% de la primera).
vector<Repeticio> simula_serie(double carrega_kg, int num_reps)
{
    double v_inicial = 1.20 - carrega_kg * 0.007;
    if (v_inicial < 0.25) {
        v_inicial = 0.25;
    }
    double perdua_per_rep = aleatori(0.02, 0.05);

    vector<Repeticio> reps;
    for (int i = 0; i < num_reps; i++) {
        Repeticio r;
        r.velocitat = v_inicial * (1 - perdua_per_rep * i) + aleatori(-0.02, 0.02);
        if (r.velocitat < 0.10) {
            r.velocitat = 0.10;
        }
        r.recorregut = aleatori(0.38, 0.45);
        r.durada = r.recorregut / r.velocitat;
        reps.push_back(r);
    }
    return reps;
}

// Munta el JSON que espera l'API (a mà, per no dependre de cap llibreria)
string crea_json(const string& maquina, const string& targeta, double carrega_kg,
                 const vector<Repeticio>& reps)
{
    ostringstream json;
    json.setf(ios::fixed);
    json.precision(2);
    json << "{\"maquina\":\"" << maquina << "\",\"targeta\":\"" << targeta
         << "\",\"carrega_kg\":" << carrega_kg << ",\"repeticions\":[";
    for (size_t i = 0; i < reps.size(); i++) {
        if (i > 0) {
            json << ",";
        }
        json << "{\"velocitat\":" << reps[i].velocitat
             << ",\"recorregut\":" << reps[i].recorregut
             << ",\"durada\":" << reps[i].durada << "}";
    }
    json << "]}";
    return json.str();
}

int main(int argc, char* argv[])
{
    string url     = argc > 1 ? argv[1] : "http://localhost/gymzero/api/serie.php";
    string maquina = argc > 2 ? argv[2] : "PRESS-01";
    string clau    = argc > 3 ? argv[3] : "token-PRESS-01";

    cout << "=== GYM-ZERO · Simulador de la màquina " << maquina << " ===\n";
    cout << "API: " << url << "\n";
    cout << "Polseres de prova: 04A1B2C3 (Marc), 04D4E5F6 (Laia), 04078899 (Pau)\n";

    while (true) {
        string targeta;
        cout << "\nAcosta la polsera (escriu el codi, o 'q' per sortir): ";
        if (!(cin >> targeta) || targeta == "q") {
            break;
        }

        double carrega;
        int num_reps;
        cout << "Càrrega (kg): ";
        if (!(cin >> carrega)) {
            break;
        }
        cout << "Repeticions: ";
        if (!(cin >> num_reps)) {
            break;
        }

        vector<Repeticio> reps = simula_serie(carrega, num_reps);
        for (size_t i = 0; i < reps.size(); i++) {
            printf("  Rep %2zu: %.2f m/s, %.2f m, %.2f s\n", i + 1,
                   reps[i].velocitat, reps[i].recorregut, reps[i].durada);
        }

        string resposta;
        long codi = envia(url, clau, crea_json(maquina, targeta, carrega, reps), resposta);

        if (codi == 0) {
            cout << "No s'ha pogut connectar amb l'API: " << resposta << "\n";
        } else if (codi == 201) {
            cout << "Guardat! " << camp(resposta, "usuari") << ", sèrie " << camp(resposta, "num_serie")
                 << " d'avui a aquesta màquina. Velocitat mitjana " << camp(resposta, "velocitat_mitjana")
                 << " m/s, pèrdua de velocitat " << camp(resposta, "perdua_velocitat_pct") << "%.\n";
        } else {
            cout << "Error " << codi << ": " << camp(resposta, "error") << "\n";
        }
    }

    cout << "Adéu!\n";
    return 0;
}
